<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatuSehatRadiologiService;

class KirimConditionController extends Controller
{
    public function index(Request $request)
    {
        $tgl1 = $request->get('tgl1', date('Y-m-d'));
        $tgl2 = $request->get('tgl2', date('Y-m-d'));
        $keyword = $request->get('keyword', '');
        $perPage = $request->get('per_page', 25);

        $query = DB::connection('simrs')->table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('mutasi_berkas', 'mutasi_berkas.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('diagnosa_pasien', 'diagnosa_pasien.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('penyakit', 'diagnosa_pasien.kd_penyakit', '=', 'penyakit.kd_penyakit')
            ->leftJoin('satu_sehat_condition', function($join) {
                $join->on('satu_sehat_condition.no_rawat', '=', 'diagnosa_pasien.no_rawat')
                     ->on('satu_sehat_condition.kd_penyakit', '=', 'diagnosa_pasien.kd_penyakit');
            })
            ->select(
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.no_ktp as no_ktp_pasien',
                'reg_periksa.stts',
                'reg_periksa.status_lanjut',
                'mutasi_berkas.kembali as pulang',
                'satu_sehat_encounter.id_encounter',
                'diagnosa_pasien.kd_penyakit',
                'penyakit.nm_penyakit',
                'satu_sehat_condition.id_condition'
            )
            ->whereBetween(DB::raw("date_format(mutasi_berkas.kembali, '%Y-%m-%d')"), [$tgl1, $tgl2]);

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('reg_periksa.no_rawat', 'like', "%$keyword%")
                    ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$keyword%")
                    ->orWhere('pasien.nm_pasien', 'like', "%$keyword%")
                    ->orWhere('diagnosa_pasien.kd_penyakit', 'like', "%$keyword%")
                    ->orWhere('penyakit.nm_penyakit', 'like', "%$keyword%");
            });
        }

        $orders = ($perPage == 'all') ? $query->paginate(1000) : $query->paginate($perPage);

        return view('satusehat.kirim_condition', compact('orders', 'tgl1', 'tgl2', 'keyword', 'perPage'));
    }

    public function post(Request $request, SatuSehatRadiologiService $service)
    {
        try {
            $data = $request->all();
            
            // 1. Get Patient ID from SatuSehat using NIK
            $patientId = $service->getPatientId($data['no_ktp_pasien']);
            if (!$patientId) return response()->json(['ok' => false, 'msg' => 'ID Pasien SatuSehat tidak ditemukan (Cek NIK)']);

            $token = $service->getAccessToken();
            if (!$token) return response()->json(['ok' => false, 'msg' => 'Gagal mendapatkan Access Token']);

            $isUpdate = !empty($data['id_condition']);
            $url = config('satusehat.base_url') . '/Condition' . ($isUpdate ? '/' . $data['id_condition'] : '');

            $payload = [
                "resourceType" => "Condition",
                "clinicalStatus" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical",
                            "code" => "active",
                            "display" => "Active"
                        ]
                    ]
                ],
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/condition-category",
                                "code" => "encounter-diagnosis",
                                "display" => "Encounter Diagnosis"
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://hl7.org/fhir/sid/icd-10",
                            "code" => $data['kd_penyakit'],
                            "display" => $data['nm_penyakit']
                        ]
                    ]
                ],
                "subject" => [
                    "reference" => "Patient/" . $patientId,
                    "display" => $data['nm_pasien']
                ],
                "encounter" => [
                    "reference" => "Encounter/" . $data['id_encounter'],
                    "display" => "Diagnosa " . $data['nm_pasien'] . " selama kunjungan dari " . $data['tgl_registrasi'] . " sampai " . $data['pulang']
                ]
            ];

            if ($isUpdate) $payload['id'] = $data['id_condition'];

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

            // Save to DB
            if (!$isUpdate) {
                DB::connection('simrs')->table('satu_sehat_condition')->updateOrInsert([
                    'no_rawat' => $data['no_rawat'],
                    'kd_penyakit' => $data['kd_penyakit'],
                    'id_condition' => $fhirId
                ]);
            }

            return response()->json(['ok' => true, 'id_condition' => $fhirId]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $err = json_decode($e->getResponse()->getBody()->getContents(), true);
            return response()->json(['ok' => false, 'msg' => $err['issue'][0]['diagnostics'] ?? 'API Error']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }
}
