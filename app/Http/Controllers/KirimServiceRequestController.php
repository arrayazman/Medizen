<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatuSehatRadiologiService;
use App\Services\PACSClient;

class KirimServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        $tgl1    = $request->get('tgl1', date('Y-m-d'));
        $tgl2    = $request->get('tgl2', date('Y-m-d'));
        $keyword = $request->get('keyword');

        $query = DB::connection('simrs')->table('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg_periksa.no_rkm_medis')
            ->join('pegawai', 'pegawai.nik', '=', 'reg_periksa.kd_dokter')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('permintaan_radiologi', 'permintaan_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('permintaan_pemeriksaan_radiologi', 'permintaan_pemeriksaan_radiologi.noorder', '=', 'permintaan_radiologi.noorder')
            ->join('jns_perawatan_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw', '=', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw')
            ->join('satu_sehat_mapping_radiologi', 'satu_sehat_mapping_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
            ->leftJoin('satu_sehat_servicerequest_radiologi', function ($join) {
                $join->on('satu_sehat_servicerequest_radiologi.noorder', '=', 'permintaan_pemeriksaan_radiologi.noorder')
                     ->on('satu_sehat_servicerequest_radiologi.kd_jenis_prw', '=', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw');
            })
            ->select(
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                DB::raw('IFNULL(pasien.no_ktp,"") AS no_ktp_pasien'),
                'reg_periksa.kd_dokter',
                'pegawai.nama as nama_dokter',
                'pegawai.no_ktp as ktpdokter',
                'satu_sehat_encounter.id_encounter',
                'permintaan_radiologi.noorder',
                'permintaan_radiologi.tgl_permintaan',
                'permintaan_radiologi.jam_permintaan',
                'permintaan_radiologi.diagnosa_klinis',
                'permintaan_radiologi.informasi_tambahan',
                'pasien.tgl_lahir',
                'pasien.jk',
                'jns_perawatan_radiologi.nm_perawatan',
                'satu_sehat_mapping_radiologi.code',
                'satu_sehat_mapping_radiologi.system',
                'satu_sehat_mapping_radiologi.display',
                'permintaan_pemeriksaan_radiologi.kd_jenis_prw',
                DB::raw('IFNULL(satu_sehat_servicerequest_radiologi.id_servicerequest,"") as id_servicerequest')
            )
            ->whereBetween('permintaan_radiologi.tgl_permintaan', [$tgl1, $tgl2]);

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('reg_periksa.no_rawat', 'like', "%$keyword%")
                  ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$keyword%")
                  ->orWhere('pasien.nm_pasien', 'like', "%$keyword%")
                  ->orWhere('permintaan_radiologi.noorder', 'like', "%$keyword%");
            });
        }

        $perPage = $request->get('per_page', 25);
        
        $queryPagination = $query->orderBy('permintaan_radiologi.noorder', 'desc');

        if ($perPage === 'all') {
            $orders = $queryPagination->paginate(1000000)->withQueryString();
        } else {
            $orders = $queryPagination->paginate((int)$perPage)->withQueryString();
        }

        return view('satusehat.kirim_servicerequest', compact('orders', 'tgl1', 'tgl2', 'keyword', 'perPage'));
    }

    public function tampil(Request $request)
    {
        return $this->index($request);
    }

    public function post(Request $request, SatuSehatRadiologiService $satusehatService, PACSClient $PACS)
    {
        $logs   = [];
        $addLog = function (string $type, string $msg) use (&$logs) {
            $logs[] = ['type' => $type, 'msg' => $msg];
        };

        try {
            $data = $request->all();

            if (empty($data['id_encounter'])) {
                throw new \Exception('Encounter ID belum ada (Pasien belum dikirim encounter).');
            }

            $noorder       = $data['noorder']            ?? '';
            $noRkmMedis    = $data['no_rkm_medis']       ?? '';
            $tglPermintaan = $data['tgl_permintaan']     ?? '';
            $dicomDate     = str_replace('-', '', $tglPermintaan);

            // ══════════════════════════════════════════════════════════
            //  PRE-STEP: Sinkronisasi AccessionNumber & Data DICOM di PACS
            //  Sebelum kirim ke SatuSehat, pastikan AccessionNumber di
            //  Orthanc sudah sesuai dengan noorder dari SIMRS.
            //  Jika AccessionNumber sudah sama persis → lewati.
            //  Kegagalan PACS sync TIDAK menghentikan proses ke SatuSehat.
            // ══════════════════════════════════════════════════════════

            $addLog('info', 'PRE-STEP: CEK & SINKRONISASI DATA PACS...');

            try {
                // [1] Cari study by AccessionNumber = noorder (Expand=true → dapat object langsung)
                $result = $PACS->post('/tools/find', [
                    'Level'  => 'Study',
                    'Query'  => ['AccessionNumber' => $noorder],
                    'Expand' => true,
                    'Limit'  => 1,
                ]);

                // [2] Fallback: PatientID + StudyDate
                if (!$result['success'] || empty($result['data'])) {
                    $result = $PACS->post('/tools/find', [
                        'Level'  => 'Study',
                        'Query'  => ['PatientID' => $noRkmMedis, 'StudyDate' => $dicomDate],
                        'Expand' => true,
                        'Limit'  => 1,
                    ]);
                }

                if ($result['success'] && !empty($result['data'])) {
                    $studyEntry = reset($result['data']);

                    // Orthanc Expand=true  → array of full objects   → $studyEntry adalah array dengan key 'ID'
                    // Orthanc Expand=false → array of ID strings      → $studyEntry adalah string UUID
                    if (is_string($studyEntry)) {
                        // Ambil full object supaya bisa baca AccessionNumber
                        $studyFull   = $PACS->getStudy($studyEntry);
                        $studyId     = $studyEntry;
                        $currentAcsn = $studyFull['MainDicomTags']['AccessionNumber'] ?? '';
                    } else {
                        $studyId     = $studyEntry['ID'] ?? null;
                        $currentAcsn = $studyEntry['MainDicomTags']['AccessionNumber'] ?? '';
                    }

                    $addLog('info', 'PACS: Menyinkronkan metadata DICOM (AccNo, StudyDescription, & Patient Info)...');

                    // Ambil deskripsi pemeriksaan
                    $simrsItems = DB::connection('simrs')
                        ->table('permintaan_pemeriksaan_radiologi')
                        ->join('jns_perawatan_radiologi',
                            'permintaan_pemeriksaan_radiologi.kd_jenis_prw', '=',
                            'jns_perawatan_radiologi.kd_jenis_prw')
                        ->where('noorder', $noorder)
                        ->pluck('jns_perawatan_radiologi.nm_perawatan')
                        ->implode(', ');

                    // Full DICOM tags (seperti updatePACSAccession di SimrsController)
                    $fullTags = [
                        'PatientID'        => $noRkmMedis,
                        'PatientName'      => strtoupper($data['nm_pasien'] ?? ''),
                        'PatientBirthDate' => str_replace('-', '', $data['tgl_lahir'] ?? ''),
                        'PatientSex'       => (($data['jk'] ?? '') === 'L' ? 'M' : 'F'),
                        'AccessionNumber'  => $noorder,
                        'StudyDescription' => $simrsItems ?: 'Radiology Study',
                    ];
                    if (!empty($data['diagnosa_klinis'])) {
                        $fullTags['AdmittingDiagnosesDescription'] = $data['diagnosa_klinis'];
                    }
                    if (!empty($data['informasi_tambahan'])) {
                        $fullTags['MedicalAlerts'] = $data['informasi_tambahan'];
                    }

                    $modifyResult = $PACS->modifyStudy($studyId, $fullTags, false);

                    // Fallback: HTTP 400 = patient ID conflict → coba study-only tags
                    if (!$modifyResult['success'] && ($modifyResult['status'] ?? 0) == 400) {
                        $addLog('info', 'PACS: Full sync gagal (400). Coba sync AccNo + Deskripsi saja...');
                        $studyOnlyTags = ['AccessionNumber' => $noorder, 'StudyDescription' => $simrsItems ?: 'Radiology Study'];
                        if (!empty($data['diagnosa_klinis'])) {
                            $studyOnlyTags['AdmittingDiagnosesDescription'] = $data['diagnosa_klinis'];
                        }
                        $modifyResult = $PACS->modifyStudy($studyId, $studyOnlyTags, false);
                    }

                    if ($modifyResult['success']) {
                        $addLog('ok', 'PACS: Metadata berhasil diperbarui.');
                    } else {
                        $addLog('info', 'PACS: Gagal update metadata (' . ($modifyResult['body'] ?? 'Unknown') . '). Proses dilanjutkan.');
                    }
                } else {
                    $addLog('info', 'PACS: Study tidak ditemukan di Orthanc untuk order ini. Sync dilewati.');
                }

            } catch (\Exception $pacsEx) {
                // PACS sync gagal tidak boleh menghentikan pengiriman ke SatuSehat
                $addLog('info', 'PACS: Sync error (dilewati): ' . $pacsEx->getMessage());
            }

            // ══════════════════════════════════════════════════════════
            //  MAIN: Kirim ServiceRequest ke SatuSehat FHIR
            // ══════════════════════════════════════════════════════════

            $addLog('info', 'MEMINTA TOKEN & ID FHIR DARI SATUSEHAT...');

            $idPasien = $satusehatService->getPatientId($data['no_ktp_pasien']);
            if (!$idPasien) {
                throw new \Exception('Patient ID tidak ditemukan. Periksa NIK KTP Pasien: ' . ($data['no_ktp_pasien'] ?: 'KOSONG'));
            }

            $idDokter = $satusehatService->getPractitionerId($data['ktpdokter']);
            if (!$idDokter) {
                throw new \Exception('Practitioner ID dokter perujuk tidak ditemukan (NIK: ' . ($data['ktpdokter'] ?? '-') . ').');
            }

            $orgId            = $satusehatService->getOrganizationId();
            $authoredOn       = \Carbon\Carbon::parse($data['tgl_permintaan'] . ' ' . $data['jam_permintaan'])->toIso8601String();
            $displayEncounter = "Permintaan {$data['nm_perawatan']} atas nama pasien {$data['nm_pasien']} No.RM {$data['no_rkm_medis']} No.Rawat {$data['no_rawat']}, pada tanggal {$data['tgl_permintaan']} {$data['jam_permintaan']}";

            $payload = [
                'resourceType' => 'ServiceRequest',
                'identifier'   => [
                    [
                        'system' => "http://sys-ids.kemkes.go.id/servicerequest/{$orgId}",
                        'value'  => "{$data['noorder']}.{$data['kd_jenis_prw']}",
                    ],
                    [
                        'system' => "http://sys-ids.kemkes.go.id/acsn/{$orgId}",
                        'value'  => "{$data['noorder']}",
                    ]
                ],
                'status'   => 'active',
                'intent'   => 'order',
                'category' => [[
                    'coding' => [[
                        'system'  => 'http://snomed.info/sct',
                        'code'    => '363679005',
                        'display' => 'Imaging',
                    ]]
                ]],
                'code' => [
                    'coding' => [[
                        'system'  => $data['system'] ?: 'http://loinc.org',
                        'code'    => $data['code'],
                        'display' => $data['display'],
                    ]],
                    'text' => $data['nm_perawatan'],
                ],
                'subject'    => ['reference' => "Patient/{$idPasien}"],
                'encounter'  => ['reference' => "Encounter/{$data['id_encounter']}", 'display' => $displayEncounter],
                'authoredOn' => $authoredOn,
                'requester'  => ['reference' => "Practitioner/{$idDokter}", 'display' => $data['nama_dokter']],
                'performer'  => [['reference' => "Organization/{$orgId}", 'display' => 'Ruang Radiologi/Petugas Radiologi']],
            ];

            if (!empty($data['diagnosa_klinis'])) {
                $payload['reasonCode'] = [['text' => $data['diagnosa_klinis']]];
            }

            $isUpdate = !empty($data['id_servicerequest']);
            if ($isUpdate) {
                $payload['id'] = $data['id_servicerequest'];
            }

            $addLog('info', ($isUpdate ? 'PUT' : 'POST') . ' → Mengirim resource ServiceRequest ke SatuSehat...');
            $respArray = $satusehatService->sendResource('ServiceRequest', $payload, $isUpdate ? $data['id_servicerequest'] : null);

            $fhirId = $respArray['id'];
            $addLog('ok', 'BERHASIL — FHIR ID: ' . $fhirId);

            $this->saveToSimrs($data['noorder'], $data['kd_jenis_prw'], $fhirId);

            return response()->json(['ok' => true, 'id_servicerequest' => $fhirId, 'logs' => $logs]);

        } catch (\Exception $e) {
            $addLog('err', $e->getMessage());
            return response()->json(['ok' => false, 'msg' => $e->getMessage(), 'logs' => $logs]);
        }
    }

    private function saveToSimrs(string $noorder, string $kdJenisPrw, string $fhirId): void
    {
        DB::connection('simrs')->table('satu_sehat_servicerequest_radiologi')->updateOrInsert(
            ['noorder' => $noorder, 'kd_jenis_prw' => $kdJenisPrw],
            ['id_servicerequest' => $fhirId]
        );
    }
}
