<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatuSehatRadiologiService;

class KirimObservationController extends Controller
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
            ->join('satu_sehat_specimen_radiologi', function($join) {
                $join->on('satu_sehat_specimen_radiologi.noorder', '=', 'permintaan_pemeriksaan_radiologi.noorder')
                     ->on('satu_sehat_specimen_radiologi.kd_jenis_prw', '=', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw');
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
            ->join('pegawai', 'pegawai.nik', '=', 'periksa_radiologi.kd_dokter')
            ->leftJoin('satu_sehat_observation_radiologi', function($join) {
                 $join->on('satu_sehat_observation_radiologi.noorder', '=', 'satu_sehat_specimen_radiologi.noorder')
                      ->on('satu_sehat_observation_radiologi.kd_jenis_prw', '=', 'satu_sehat_specimen_radiologi.kd_jenis_prw');
            })
            ->select(
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                DB::raw('IFNULL(pasien.no_ktp,"") AS no_ktp_pasien'),
                'satu_sehat_encounter.id_encounter',
                'permintaan_radiologi.noorder',
                'permintaan_radiologi.tgl_hasil',
                'permintaan_radiologi.jam_hasil',
                'jns_perawatan_radiologi.nm_perawatan',
                'satu_sehat_mapping_radiologi.code',
                'satu_sehat_mapping_radiologi.system',
                'satu_sehat_mapping_radiologi.display',
                'hasil_radiologi.hasil as expertise',
                'permintaan_pemeriksaan_radiologi.kd_jenis_prw',
                'satu_sehat_specimen_radiologi.id_specimen',
                'periksa_radiologi.kd_dokter',
                'pegawai.nama as nama_dokter',
                'pegawai.no_ktp as ktppraktisi',
                DB::raw('IFNULL(satu_sehat_observation_radiologi.id_observation,"") as id_observation')
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

        return view('satusehat.kirim_observation', compact('orders', 'tgl1', 'tgl2', 'keyword'));
    }

    public function post(Request $request)
    {
        $logs = [];
        $addLog = function ($type, $msg) use (&$logs) { $logs[] = ['type' => $type, 'msg' => $msg]; };

        try {
            $data = $request->all();
            if (empty($data['id_specimen'])) throw new \Exception('Specimen ID belum ada.');
            if (empty($data['id_encounter'])) throw new \Exception('Encounter ID belum ada.');

            $addLog('info', 'MEMINTA TOKEN & ID FHIR DARI SATUSEHAT...');
            $idPasien = $this->ssService->getPatientId($data['no_ktp_pasien']);
            if (!$idPasien) throw new \Exception('Patient ID tidak ditemukan. Periksa NIK KTP Pasien: ' . $data['no_ktp_pasien']);
            
            $idDokter = $this->ssService->getPractitionerId($data['ktppraktisi']);
            if (!$idDokter) throw new \Exception('Practitioner ID dokter tidak ditemukan (NIK: '.$data['ktppraktisi'].').');

            $orgId = $this->ssService->getOrganizationId();
            
            $tglJam = $data['tgl_hasil'] . ' ' . $data['jam_hasil'];
            $effectiveDateTime = \Carbon\Carbon::parse($tglJam)->toIso8601String();
            
            $expertiseStr = strip_tags(str_replace(["\r\n", "\r", "\n", "\n\r"], "<br>", $data['expertise'] ?? ''));

            $displayEncounter = "Hasil Pemeriksaan Radiologi {$data['nm_perawatan']} No.Rawat {$data['no_rawat']}, Atas Nama Pasien {$data['nm_pasien']}, No.RM {$data['no_rkm_medis']}, Pada Tanggal {$tglJam}";

            $payload = [
                'resourceType' => 'Observation',
                'status' => 'final',
                'category' => [
                    [
                        'coding' => [
                            [
                                'system' => "http://terminology.hl7.org/CodeSystem/observation-category",
                                'code' => "imaging",
                                'display' => "Imaging"
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
                'performer' => [
                    [
                        'reference' => "Practitioner/{$idDokter}"
                    ]
                ],
                'encounter' => [
                    'reference' => "Encounter/{$data['id_encounter']}",
                    'display' => $displayEncounter
                ],
                'specimen' => [
                    'reference' => "Specimen/{$data['id_specimen']}"
                ],
                'effectiveDateTime' => $effectiveDateTime,
                'valueString' => $expertiseStr
            ];

            if ($data['id_observation']) {
                $payload['id'] = $data['id_observation'];
            } else {
                $payload['identifier'] = [
                    [
                        'system' => "http://sys-ids.kemkes.go.id/observation/{$orgId}",
                        'value' => "{$data['noorder']}.{$data['kd_jenis_prw']}"
                    ]
                ];
            }

            $isUpdate = !empty($data['id_observation']);
            
            $addLog('info', ($isUpdate ? 'UPDATE' : 'KIRIM') . ' RESOURCE OBSERVATION...');
            $res = $this->ssService->sendResource('Observation', $payload, $data['id_observation'] ?: null);
            
            $fhirId = $res['id'];
            $addLog('ok', "BERHASIL: " . $fhirId);

            // Save to SIMRS
            $this->saveToSimrs($data['noorder'], $data['kd_jenis_prw'], $fhirId);

            return response()->json(['ok' => true, 'id_observation' => $fhirId, 'logs' => $logs]);

        } catch (\Exception $e) {
            $addLog('err', $e->getMessage());
            return response()->json(['ok' => false, 'msg' => $e->getMessage(), 'logs' => $logs]);
        }
    }

    private function saveToSimrs($noorder, $kdJenisPrw, $fhirId)
    {
        DB::connection('simrs')->table('satu_sehat_observation_radiologi')->updateOrInsert(
            ['noorder' => $noorder, 'kd_jenis_prw' => $kdJenisPrw],
            ['id_observation' => $fhirId]
        );
    }
}
