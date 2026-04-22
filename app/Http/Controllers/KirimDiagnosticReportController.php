<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatuSehatRadiologiService;

class KirimDiagnosticReportController extends Controller
{
    private $ssService;

    public function __construct(SatuSehatRadiologiService $ssService)
    {
        $this->ssService = $ssService;
    }

    public function index(Request $request)
    {
        $tgl1 = $request->get('tgl1', date('Y-m-d'));
        $tgl2 = $request->get('tgl2', date('Y-m-d'));
        $keyword = $request->get('keyword');

        $query = DB::connection('simrs')->table('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg_periksa.no_rkm_medis')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('permintaan_radiologi', 'permintaan_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('permintaan_pemeriksaan_radiologi', 'permintaan_pemeriksaan_radiologi.noorder', '=', 'permintaan_radiologi.noorder')
            ->join('jns_perawatan_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw', '=', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw')
            ->join('satu_sehat_mapping_radiologi', 'satu_sehat_mapping_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
            ->join('satu_sehat_servicerequest_radiologi', function($join) {
                $join->on('satu_sehat_servicerequest_radiologi.noorder', '=', 'permintaan_pemeriksaan_radiologi.noorder')
                     ->on('satu_sehat_servicerequest_radiologi.kd_jenis_prw', '=', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw');
            })
            ->join('satu_sehat_specimen_radiologi', function($join) {
                $join->on('satu_sehat_specimen_radiologi.noorder', '=', 'satu_sehat_servicerequest_radiologi.noorder')
                     ->on('satu_sehat_specimen_radiologi.kd_jenis_prw', '=', 'satu_sehat_servicerequest_radiologi.kd_jenis_prw');
            })
            ->join('periksa_radiologi', function($join) {
                 $join->on('periksa_radiologi.no_rawat', '=', 'permintaan_radiologi.no_rawat')
                      ->on('periksa_radiologi.tgl_periksa', '=', 'permintaan_radiologi.tgl_hasil')
                      ->on('periksa_radiologi.jam', '=', 'permintaan_radiologi.jam_hasil')
                      ->on('periksa_radiologi.dokter_perujuk', '=', 'permintaan_radiologi.dokter_perujuk');
            })
            ->join('hasil_radiologi', function($join) {
                 $join->on('hasil_radiologi.no_rawat', '=', 'periksa_radiologi.no_rawat')
                      ->on('hasil_radiologi.tgl_periksa', '=', 'periksa_radiologi.tgl_periksa')
                      ->on('hasil_radiologi.jam', '=', 'periksa_radiologi.jam');
            })
            ->join('satu_sehat_observation_radiologi', function($join) {
                 $join->on('satu_sehat_observation_radiologi.noorder', '=', 'satu_sehat_specimen_radiologi.noorder')
                      ->on('satu_sehat_observation_radiologi.kd_jenis_prw', '=', 'satu_sehat_specimen_radiologi.kd_jenis_prw');
            })
            ->leftJoin('satu_sehat_diagnosticreport_radiologi', function($join) {
                 $join->on('satu_sehat_diagnosticreport_radiologi.noorder', '=', 'satu_sehat_servicerequest_radiologi.noorder')
                      ->on('satu_sehat_diagnosticreport_radiologi.kd_jenis_prw', '=', 'satu_sehat_servicerequest_radiologi.kd_jenis_prw');
            })
            ->leftJoin('satu_sehat_imagingstudy_radiologi', function($join) {
                 $join->on('satu_sehat_imagingstudy_radiologi.noorder', '=', 'satu_sehat_servicerequest_radiologi.noorder')
                      ->on('satu_sehat_imagingstudy_radiologi.kd_jenis_prw', '=', 'satu_sehat_servicerequest_radiologi.kd_jenis_prw');
            })
            ->join('pegawai', 'pegawai.nik', '=', 'periksa_radiologi.kd_dokter')
            ->select(
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                DB::raw('IFNULL(pasien.no_ktp,"") AS no_ktp_pasien'),
                'periksa_radiologi.kd_dokter',
                'pegawai.nama as nama_dokter',
                'pegawai.no_ktp as ktpdokter',
                'satu_sehat_encounter.id_encounter',
                'permintaan_radiologi.noorder',
                'permintaan_radiologi.tgl_hasil',
                'permintaan_radiologi.jam_hasil',
                'jns_perawatan_radiologi.nm_perawatan',
                'satu_sehat_mapping_radiologi.code',
                'satu_sehat_mapping_radiologi.system',
                'satu_sehat_mapping_radiologi.display',
                'permintaan_pemeriksaan_radiologi.kd_jenis_prw',
                'satu_sehat_servicerequest_radiologi.id_servicerequest',
                'satu_sehat_specimen_radiologi.id_specimen',
                'satu_sehat_observation_radiologi.id_observation',
                DB::raw('IFNULL(satu_sehat_imagingstudy_radiologi.id_imaging,"") as id_imaging'),
                'hasil_radiologi.hasil as expertise',
                DB::raw('IFNULL(satu_sehat_diagnosticreport_radiologi.id_diagnosticreport,"") as id_diagnosticreport')
            )
            ->whereBetween('permintaan_radiologi.tgl_permintaan', [$tgl1, $tgl2])
            ->where('permintaan_radiologi.tgl_hasil', '!=', '0000-00-00');

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('reg_periksa.no_rawat', 'like', "%$keyword%")
                  ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$keyword%")
                  ->orWhere('pasien.nm_pasien', 'like', "%$keyword%")
                  ->orWhere('permintaan_radiologi.noorder', 'like', "%$keyword%");
            });
        }

        $orders = $query->orderBy('permintaan_radiologi.noorder', 'desc')
                        ->paginate(25)
                        ->withQueryString();

        return view('satusehat.kirim_diagnosticreport', compact('orders', 'tgl1', 'tgl2', 'keyword'));
    }

    public function post(Request $request)
    {
        $logs = [];
        $addLog = function ($type, $msg) use (&$logs) { $logs[] = ['type' => $type, 'msg' => $msg]; };

        try {
            $data = $request->all();
            if (empty($data['id_encounter'])) throw new \Exception('Encounter ID belum ada.');
            if (empty($data['id_servicerequest'])) throw new \Exception('ServiceRequest ID belum ada.');
            if (empty($data['id_specimen'])) throw new \Exception('Specimen ID belum ada.');
            if (empty($data['id_observation'])) throw new \Exception('Observation ID belum ada.');

            $addLog('info', 'MEMINTA TOKEN & ID FHIR DARI SATUSEHAT...');
            $idPasien = $this->ssService->getPatientId($data['no_ktp_pasien']);
            if (!$idPasien) throw new \Exception('Patient ID tidak ditemukan. Periksa NIK KTP Pasien: ' . $data['no_ktp_pasien']);

            $idDokter = $this->ssService->getPractitionerId($data['ktpdokter']);
            if (!$idDokter) throw new \Exception('Practitioner ID dokter radiologi tidak ditemukan (NIK: '.$data['ktpdokter'].').');

            $orgId = $this->ssService->getOrganizationId();
            
            $dateTime = \Carbon\Carbon::parse($data['tgl_hasil'] . ' ' . $data['jam_hasil'])->toIso8601String();
            
            $conclusionStr = strip_tags(str_replace(["\r\n", "\r", "\n", "\n\r"], "<br>", $data['expertise'] ?? ''));

            $payload = [
                'resourceType' => 'DiagnosticReport',
                'status' => 'final',
                'category' => [
                    [
                        'coding' => [
                            [
                                'system' => "http://terminology.hl7.org/CodeSystem/v2-0074",
                                'code' => "RAD",
                                'display' => "Radiology"
                            ]
                        ]
                    ]
                ],
                'code' => [
                    'coding' => [
                        [
                            'system' => $data['system'] ?: 'http://loinc.org',
                            'code' => $data['code'],
                            'display' => $data['display']
                        ]
                    ]
                ],
                'subject' => [
                    'reference' => "Patient/{$idPasien}"
                ],
                'encounter' => [
                    'reference' => "Encounter/{$data['id_encounter']}"
                ],
                'effectiveDateTime' => $dateTime,
                'issued' => $dateTime,
                'performer' => [
                    [
                        'reference' => "Practitioner/{$idDokter}"
                    ]
                ],
                'specimen' => [
                    [
                        'reference' => "Specimen/{$data['id_specimen']}"
                    ]
                ],
                'result' => [
                    [
                        'reference' => "Observation/{$data['id_observation']}"
                    ]
                ],
                'basedOn' => [
                    [
                        'reference' => "ServiceRequest/{$data['id_servicerequest']}"
                    ]
                ],
                'conclusion' => $conclusionStr
            ];

            // TAUTKAN ID IMAGINGSTUDY (Agar tombol gambar muncul di APK SatuSehat Mobile)
            if (!empty($data['id_imaging'])) {
                $payload['imagingStudy'] = [
                    [
                        'reference' => "ImagingStudy/{$data['id_imaging']}"
                    ]
                ];
            }

            if ($data['id_diagnosticreport']) {
                $payload['id'] = $data['id_diagnosticreport'];
            } else {
                $payload['identifier'] = [
                    [
                        'system' => "http://sys-ids.kemkes.go.id/diagnostic/{$orgId}/rad",
                        'use' => 'official',
                        'value' => "{$data['noorder']}.{$data['kd_jenis_prw']}"
                    ]
                ];
            }

            $isUpdate = !empty($data['id_diagnosticreport']);
            
            $addLog('info', ($isUpdate ? 'UPDATE' : 'KIRIM') . ' RESOURCE DIAGNOSTICREPORT...');
            $res = $this->ssService->sendResource('DiagnosticReport', $payload, $data['id_diagnosticreport'] ?: null);
            
            $fhirId = $res['id'];
            $addLog('ok', "BERHASIL: " . $fhirId);

            // Save to SIMRS
            $this->saveToSimrs($data['noorder'], $data['kd_jenis_prw'], $fhirId);

            return response()->json(['ok' => true, 'id_diagnosticreport' => $fhirId, 'logs' => $logs]);

        } catch (\Exception $e) {
            $addLog('err', $e->getMessage());
            return response()->json(['ok' => false, 'msg' => $e->getMessage(), 'logs' => $logs]);
        }
    }

    private function saveToSimrs($noorder, $kdJenisPrw, $fhirId)
    {
        DB::connection('simrs')->table('satu_sehat_diagnosticreport_radiologi')->updateOrInsert(
            ['noorder' => $noorder, 'kd_jenis_prw' => $kdJenisPrw],
            ['id_diagnosticreport' => $fhirId]
        );
    }
}
