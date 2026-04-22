<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatuSehatRadiologiService;

class KirimObservationLabPKController extends Controller
{
    public function index(Request $request)
    {
        $tgl1 = $request->get('tgl1', date('Y-m-d'));
        $tgl2 = $request->get('tgl2', date('Y-m-d'));
        $keyword = $request->get('keyword', '');
        $perPage = $request->get('per_page', 25);

        $query = DB::connection('simrs')->table('detail_periksa_lab')
            ->join('reg_periksa', 'detail_periksa_lab.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('template_laborat', 'detail_periksa_lab.id_template', '=', 'template_laborat.id_template')
            ->join('satu_sehat_mapping_lab', 'template_laborat.id_template', '=', 'satu_sehat_mapping_lab.id_template')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('periksa_lab', function($join) {
                $join->on('periksa_lab.no_rawat', '=', 'detail_periksa_lab.no_rawat')
                     ->on('periksa_lab.tgl_periksa', '=', 'detail_periksa_lab.tgl_periksa')
                     ->on('periksa_lab.jam', '=', 'detail_periksa_lab.jam');
            })
            ->join('pegawai', 'pegawai.nik', '=', 'periksa_lab.kd_dokter')
            ->join('satu_sehat_specimen_lab', function($join) {
                $join->on('satu_sehat_specimen_lab.noorder', '=', 'periksa_lab.noorder')
                     ->on('satu_sehat_specimen_lab.kd_jenis_prw', '=', 'periksa_lab.kd_jenis_prw');
            })
            ->leftJoin('satu_sehat_observation_lab', function($join) {
                $join->on('satu_sehat_observation_lab.noorder', '=', 'periksa_lab.noorder')
                     ->on('satu_sehat_observation_lab.id_template', '=', 'detail_periksa_lab.id_template');
            })
            ->select(
                'detail_periksa_lab.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.no_ktp as no_ktp_pasien',
                'periksa_lab.noorder',
                'detail_periksa_lab.tgl_periksa',
                'detail_periksa_lab.jam',
                'template_laborat.Pemeriksaan',
                'satu_sehat_mapping_lab.code',
                'satu_sehat_mapping_lab.system',
                'satu_sehat_mapping_lab.display',
                'detail_periksa_lab.nilai',
                'detail_periksa_lab.id_template',
                'satu_sehat_specimen_lab.id_specimen',
                'pegawai.nama as nm_dokter',
                'pegawai.no_ktp as no_ktp_dokter',
                'satu_sehat_encounter.id_encounter',
                'satu_sehat_observation_lab.id_observation',
                'periksa_lab.kd_jenis_prw'
            )
            ->whereBetween('detail_periksa_lab.tgl_periksa', [$tgl1, $tgl2]);

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('detail_periksa_lab.no_rawat', 'like', "%$keyword%")
                  ->orWhere('pasien.nm_pasien', 'like', "%$keyword%")
                  ->orWhere('template_laborat.Pemeriksaan', 'like', "%$keyword%");
            });
        }

        $orders = ($perPage == 'all') ? $query->paginate(1000) : $query->paginate($perPage);

        return view('satusehat.kirim_observation_lab_pk', compact('orders', 'tgl1', 'tgl2', 'keyword', 'perPage'));
    }

    public function post(Request $request, SatuSehatRadiologiService $service)
    {
        try {
            $data = $request->all();
            
            $patientId = $service->getPatientId($data['no_ktp_pasien']);
            $practitionerId = $service->getPractitionerId($data['no_ktp_dokter']);
            if (!$patientId || !$practitionerId) return response()->json(['ok' => false, 'msg' => 'ID Pasien/Praktisi tidak ditemukan']);

            $token = $service->getAccessToken();
            $isUpdate = !empty($data['id_observation']);
            $url = config('satusehat.base_url') . '/Observation' . ($isUpdate ? '/' . $data['id_observation'] : '');

            $payload = [
                "resourceType" => "Observation",
                "status" => "final",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "laboratory",
                                "display" => "Laboratory"
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => $data['system'],
                            "code" => $data['code'],
                            "display" => $data['display']
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/" . $patientId],
                "performer" => [["reference" => "Practitioner/" . $practitionerId]],
                "encounter" => ["reference" => "Encounter/" . $data['id_encounter']],
                "specimen" => ["reference" => "Specimen/" . $data['id_specimen']],
                "effectiveDateTime" => $data['tgl_periksa'] . 'T' . $data['jam'] . '+07:00',
                "valueString" => (string)$data['nilai']
            ];

            if ($isUpdate) $payload['id'] = $data['id_observation'];

            $client = new \GuzzleHttp\Client();
            $response = $client->request($isUpdate ? 'PUT' : 'POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);

            $resData = json_decode($response->getBody()->getContents(), true);
            $fhirId = $resData['id'];

            if (!$isUpdate) {
                DB::connection('simrs')->table('satu_sehat_observation_lab')->insert([
                    'noorder' => $data['noorder'],
                    'kd_pemeriksaan' => $data['kd_jenis_prw'], // Often used as part of key
                    'id_template' => $data['id_template'],
                    'id_observation' => $fhirId
                ]);
            }

            return response()->json(['ok' => true, 'id_observation' => $fhirId]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }
}
