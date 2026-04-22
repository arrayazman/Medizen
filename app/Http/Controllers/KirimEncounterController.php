<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatuSehatRadiologiService;
use Carbon\Carbon;

class KirimEncounterController extends Controller
{
    public function index(Request $request)
    {
        $tgl1 = $request->get('tgl1', date('Y-m-d'));
        $tgl2 = $request->get('tgl2', date('Y-m-d'));
        $keyword = $request->get('keyword');

        $query = DB::connection('simrs')->table('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg_periksa.no_rkm_medis')
            ->join('pegawai', 'pegawai.nik', '=', 'reg_periksa.kd_dokter')
            ->join('poliklinik', 'poliklinik.kd_poli', '=', 'reg_periksa.kd_poli')
            ->join('satu_sehat_mapping_lokasi_ralan', 'satu_sehat_mapping_lokasi_ralan.kd_poli', '=', 'poliklinik.kd_poli')
            ->leftJoin('mutasi_berkas', 'mutasi_berkas.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->select(
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                DB::raw('IFNULL(pasien.no_ktp,"") AS no_ktp_pasien'),
                'reg_periksa.kd_dokter',
                'pegawai.nama as nama_dokter',
                'pegawai.no_ktp as ktpdokter',
                'reg_periksa.kd_poli',
                'poliklinik.nm_poli',
                'satu_sehat_mapping_lokasi_ralan.id_lokasi_satusehat',
                'reg_periksa.stts',
                'reg_periksa.status_lanjut',
                'mutasi_berkas.kembali',
                DB::raw('IFNULL(satu_sehat_encounter.id_encounter,"") as id_encounter')
            )
            ->whereBetween('reg_periksa.tgl_registrasi', [$tgl1, $tgl2]);

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('reg_periksa.no_rawat', 'like', "%$keyword%")
                  ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$keyword%")
                  ->orWhere('pasien.nm_pasien', 'like', "%$keyword%")
                  ->orWhere('pegawai.nama', 'like', "%$keyword%")
                  ->orWhere('poliklinik.nm_poli', 'like', "%$keyword%");
            });
        }

        $orders = $query->orderBy('reg_periksa.no_rawat', 'desc')
                        ->paginate(25)
                        ->withQueryString();

        return view('satusehat.kirim_encounter', compact('orders', 'tgl1', 'tgl2', 'keyword'));
    }

    public function tampil(Request $request)
    {
        return $this->index($request);
    }

    public function post(Request $request, SatuSehatRadiologiService $satusehatService)
    {
        $logs = [];
        $addLog = function ($type, $msg) use (&$logs) { $logs[] = ['type' => $type, 'msg' => $msg]; };

        try {
            $data = $request->all();
            
            if (empty($data['id_lokasi_satusehat'])) throw new \Exception('Mapping Lokasi Poliklinik/Unit belum ada.');

            $addLog('info', 'MEMINTA TOKEN & ID FHIR DARI SATUSEHAT...');

            $idPasien = $satusehatService->getPatientId($data['no_ktp_pasien']);
            if (!$idPasien) throw new \Exception('Patient ID tidak ditemukan. Periksa NIK KTP Pasien: ' . ($data['no_ktp_pasien'] ?: 'KOSONG'));

            $idDokter = $satusehatService->getPractitionerId($data['ktpdokter']);
            if (!$idDokter) throw new \Exception('Practitioner ID dokter perujuk tidak ditemukan (NIK: '.$data['ktpdokter'].').');

            $orgId = $satusehatService->getOrganizationId();
            
            $startPeriod = Carbon::parse($data['tgl_registrasi'] . ' ' . $data['jam_reg'])->toIso8601String();
            $endPeriod = !empty($data['kembali']) && $data['kembali'] != '0000-00-00 00:00:00'
                            ? Carbon::parse($data['kembali'])->toIso8601String() 
                            : Carbon::parse($data['tgl_registrasi'] . ' ' . $data['jam_reg'])->addHours(2)->toIso8601String(); // Default end time if null

            $isRalan = $data['status_lanjut'] === 'Ralan';
            $classCode = $isRalan ? 'AMB' : 'IMP';
            $classDisplay = $isRalan ? 'ambulatory' : 'inpatient encounter';

            $payload = [
                'resourceType' => 'Encounter',
                'status' => 'arrived',
                'class' => [
                    'system' => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                    'code' => $classCode,
                    'display' => $classDisplay
                ],
                'subject' => [
                    'reference' => "Patient/{$idPasien}",
                    'display' => $data['nm_pasien']
                ],
                'participant' => [
                    [
                        'type' => [
                            [
                                'coding' => [
                                    [
                                        'system' => "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                        'code' => "ATND",
                                        'display' => "attender"
                                    ]
                                ]
                            ]
                        ],
                        'individual' => [
                            'reference' => "Practitioner/{$idDokter}",
                            'display' => $data['nama_dokter']
                        ]
                    ]
                ],
                'period' => [
                    'start' => $startPeriod
                ],
                'location' => [
                    [
                        'location' => [
                            'reference' => "Location/{$data['id_lokasi_satusehat']}",
                            'display' => $data['nm_poli']
                        ]
                    ]
                ],
                'statusHistory' => [
                    [
                        'status' => 'arrived',
                        'period' => [
                            'start' => $startPeriod,
                            'end' => $endPeriod
                        ]
                    ]
                ],
                'serviceProvider' => [
                    'reference' => "Organization/{$orgId}"
                ],
                'identifier' => [
                    [
                        'system' => "http://sys-ids.kemkes.go.id/encounter/{$orgId}",
                        'value' => $data['no_rawat']
                    ]
                ]
            ];

            $isUpdate = !empty($data['id_encounter']);
            if ($isUpdate) $payload['id'] = $data['id_encounter'];

            $addLog('info', ($isUpdate ? 'PUT' : 'POST') . " → Mengirim resource Encounter");
            $respArray = $satusehatService->sendResource('Encounter', $payload, $isUpdate ? $data['id_encounter'] : null);
            
            $fhirId = $respArray['id'] ?? null;
            if (!$fhirId) throw new \Exception('Gagal mendapatkan ID resource dari respons.');
            
            $addLog('ok', "Berhasil: {$fhirId}");

            // Save to SIMRS
            $this->saveToSimrs($data['no_rawat'], $fhirId);

            return response()->json(['ok' => true, 'id_encounter' => $fhirId, 'logs' => $logs]);

        } catch (\Exception $e) {
            $addLog('err', $e->getMessage());
            return response()->json(['ok' => false, 'msg' => $e->getMessage(), 'logs' => $logs]);
        }
    }

    public function finish(Request $request, SatuSehatRadiologiService $satusehatService)
    {
        $logs = [];
        $addLog = function ($type, $msg) use (&$logs) { $logs[] = ['type' => $type, 'msg' => $msg]; };

        try {
            $data = $request->all();
            $idEncounter = $data['id_encounter'] ?? null;
            
            if (!$idEncounter) throw new \Exception('ID Encounter FHIR belum ada. Silakan kirim data sebagai Arrived terlebih dahulu.');
            if (empty($data['id_lokasi_satusehat'])) throw new \Exception('Mapping Lokasi Poliklinik/Unit belum ada.');

            $addLog('info', 'MEMPERPROSES FINISH ENCOUNTER...');

            $idPasien = $satusehatService->getPatientId($data['no_ktp_pasien']);
            $idDokter = $satusehatService->getPractitionerId($data['ktpdokter']);
            $orgId = $satusehatService->getOrganizationId();

            $startPeriod = Carbon::parse($data['tgl_registrasi'] . ' ' . $data['jam_reg'])->toIso8601String();
            
            // Waktu selesai: Gunakan 'kembali' (checkout) jika ada, jika tidak gunakan waktu sekarang
            $now = Carbon::now('Asia/Jakarta');
            $endTime = !empty($data['kembali']) && $data['kembali'] != '0000-00-00 00:00:00'
                            ? Carbon::parse($data['kembali'])->toIso8601String() 
                            : $now->toIso8601String();

            $isRalan = $data['status_lanjut'] === 'Ralan';
            $classCode = $isRalan ? 'AMB' : 'IMP';
            $classDisplay = $isRalan ? 'ambulatory' : 'inpatient encounter';

            // SATUSEHAT WAJIB DIAGNOSA UNTUK STATUS FINISHED
            $diagnosa = DB::connection('simrs')->table('diagnosa_pasien')
                ->join('penyakit', 'penyakit.kd_penyakit', '=', 'diagnosa_pasien.kd_penyakit')
                ->where('no_rawat', $data['no_rawat'])
                ->select('diagnosa_pasien.kd_penyakit', 'penyakit.nm_penyakit')
                ->orderBy('prioritas', 'asc')
                ->first();

            if (!$diagnosa) {
                throw new \Exception('Gagal Finish: Diagnosa (ICD-10) Pasien belum diinput di SIMRS untuk No.Rawat: ' . $data['no_rawat']);
            }

            $payload = [
                'resourceType' => 'Encounter',
                'id' => $idEncounter,
                'identifier' => [
                    [
                        'system' => "http://sys-ids.kemkes.go.id/encounter/{$orgId}",
                        'value' => $data['no_rawat']
                    ]
                ],
                'status' => 'finished',
                'class' => [
                    'system' => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                    'code' => $classCode,
                    'display' => $classDisplay
                ],
                'subject' => [
                    'reference' => "Patient/{$idPasien}",
                    'display' => $data['nm_pasien']
                ],
                'participant' => [
                    [
                        'type' => [
                            [
                                'coding' => [
                                    [
                                        'system' => "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                        'code' => "ATND",
                                        'display' => "attender"
                                    ]
                                ]
                            ]
                        ],
                        'individual' => [
                            'reference' => "Practitioner/{$idDokter}",
                            'display' => $data['nama_dokter']
                        ]
                    ]
                ],
                'period' => [
                    'start' => $startPeriod,
                    'end' => $endTime
                ],
                'location' => [
                    [
                        'location' => [
                            'reference' => "Location/{$data['id_lokasi_satusehat']}",
                            'display' => $data['nm_poli']
                        ]
                    ]
                ],
                'diagnosis' => [
                    [
                        'rank' => 1,
                        'condition' => [
                            // Nota: Di SatuSehat idealnya reference ke resource Condition, 
                            // namun untuk update status, seringkali cukup dengan textual atau mapping Condition jika sudah ada.
                            // Di sistem ini kita asumsikan reference text/display dari SIMRS.
                            'display' => $diagnosa->kd_penyakit . ' - ' . $diagnosa->nm_penyakit
                        ],
                        'use' => [
                            'coding' => [
                                [
                                    'system' => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                    'code' => "billing",
                                    'display' => "Billing"
                                ]
                            ]
                        ]
                    ]
                ],
                'statusHistory' => [
                    [
                        'status' => 'arrived',
                        'period' => [
                            'start' => $startPeriod,
                            'end' => $endTime
                        ]
                    ],
                    [
                        'status' => 'finished',
                        'period' => [
                            'start' => $endTime,
                            'end' => $endTime
                        ]
                    ]
                ],
                'serviceProvider' => [
                    'reference' => "Organization/{$orgId}"
                ]
            ];

            $addLog('info', "PUT → Mengirim update status Finished ke SatuSehat...");
            $respArray = $satusehatService->sendResource('Encounter', $payload, $idEncounter);
            
            $addLog('ok', "Berhasil: Encounter telah berstatus FINISHED.");

            return response()->json(['ok' => true, 'id_encounter' => $idEncounter, 'logs' => $logs]);

        } catch (\Exception $e) {
            $addLog('err', $e->getMessage());
            return response()->json(['ok' => false, 'msg' => $e->getMessage(), 'logs' => $logs]);
        }
    }

    private function saveToSimrs($noRawat, $fhirId)
    {
        $exists = DB::connection('simrs')->table('satu_sehat_encounter')->where('no_rawat', $noRawat)->exists();
        if ($exists) {
            DB::connection('simrs')->table('satu_sehat_encounter')->where('no_rawat', $noRawat)->update(['id_encounter' => $fhirId]);
        } else {
            DB::connection('simrs')->table('satu_sehat_encounter')->insert(['no_rawat' => $noRawat, 'id_encounter' => $fhirId]);
        }
    }
}
