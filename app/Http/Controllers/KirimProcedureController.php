<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatuSehatRadiologiService;

class KirimProcedureController extends Controller
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
            ->join('prosedur_pasien', 'prosedur_pasien.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('icd9', 'prosedur_pasien.kode', '=', 'icd9.kode')
            ->leftJoin('satu_sehat_procedure', function($join) {
                $join->on('satu_sehat_procedure.no_rawat', '=', 'prosedur_pasien.no_rawat')
                     ->on('satu_sehat_procedure.kode', '=', 'prosedur_pasien.kode');
            })
            ->select(
                DB::raw("concat(reg_periksa.tgl_registrasi, 'T', reg_periksa.jam_reg, '+07:00') as performed_start"),
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.no_ktp as no_ktp_pasien',
                'reg_periksa.stts',
                'reg_periksa.status_lanjut',
                DB::raw("concat(mutasi_berkas.kembali, 'T00:00:00+07:00') as performed_end"),
                'satu_sehat_encounter.id_encounter',
                'prosedur_pasien.kode',
                'icd9.deskripsi_panjang',
                'satu_sehat_procedure.id_procedure'
            )
            ->whereBetween(DB::raw("date_format(mutasi_berkas.kembali, '%Y-%m-%d')"), [$tgl1, $tgl2]);

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('reg_periksa.no_rawat', 'like', "%$keyword%")
                    ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$keyword%")
                    ->orWhere('pasien.nm_pasien', 'like', "%$keyword%")
                    ->orWhere('prosedur_pasien.kode', 'like', "%$keyword%")
                    ->orWhere('icd9.deskripsi_panjang', 'like', "%$keyword%");
            });
        }

        $orders = ($perPage == 'all') ? $query->paginate(1000) : $query->paginate($perPage);

        return view('satusehat.kirim_procedure', compact('orders', 'tgl1', 'tgl2', 'keyword', 'perPage'));
    }

    public function post(Request $request, SatuSehatRadiologiService $service)
    {
        try {
            $data = $request->all();
            
            $patientId = $service->getPatientId($data['no_ktp_pasien']);
            if (!$patientId) return response()->json(['ok' => false, 'msg' => 'ID Pasien SatuSehat tidak ditemukan']);

            $token = $service->getAccessToken();
            if (!$token) return response()->json(['ok' => false, 'msg' => 'Gagal mendapatkan Access Token']);

            $isUpdate = !empty($data['id_procedure']);
            $url = config('satusehat.base_url') . '/Procedure' . ($isUpdate ? '/' . $data['id_procedure'] : '');

            $payload = [
                "resourceType" => "Procedure",
                "status" => "completed",
                "category" => [
                    "coding" => [
                        [
                            "system" => "http://snomed.info/sct",
                            "code" => "103693007",
                            "display" => "Diagnostic procedure"
                        ]
                    ],
                    "text" => "Diagnostic procedure"
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                            "code" => $data['kode'],
                            "display" => $data['deskripsi_panjang']
                        ]
                    ]
                ],
                "subject" => [
                    "reference" => "Patient/" . $patientId,
                    "display" => $data['nm_pasien']
                ],
                "encounter" => [
                    "reference" => "Encounter/" . $data['id_encounter'],
                    "display" => "Prosedur " . $data['nm_pasien'] . " selama kunjungan"
                ],
                "performedPeriod" => [
                    "start" => $data['performed_start'],
                    "end" => $data['performed_end']
                ]
            ];

            if ($isUpdate) $payload['id'] = $data['id_procedure'];

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
                DB::connection('simrs')->table('satu_sehat_procedure')->updateOrInsert([
                    'no_rawat' => $data['no_rawat'],
                    'kode' => $data['kode'],
                    'id_procedure' => $fhirId
                ]);
            }

            return response()->json(['ok' => true, 'id_procedure' => $fhirId]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $err = json_decode($e->getResponse()->getBody()->getContents(), true);
            return response()->json(['ok' => false, 'msg' => $err['issue'][0]['diagnostics'] ?? 'API Error']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }
}
