<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatuSehatRadiologiService;

class KirimObservationTTVController extends Controller
{
    public function index(Request $request)
    {
        $tgl1 = $request->get('tgl1', date('Y-m-d'));
        $tgl2 = $request->get('tgl2', date('Y-m-d'));
        $keyword = $request->get('keyword', '');
        $perPage = $request->get('per_page', 25);

        // Fetching Vital Signs (TTV) from pemeriksaan_ralan and pemeriksaan_ranap
        // We'll union them for a complete list
        $queryRalan = DB::connection('simrs')->table('pemeriksaan_ralan')
            ->join('reg_periksa', 'pemeriksaan_ralan.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pegawai', 'pegawai.nik', '=', 'pemeriksaan_ralan.nip')
            ->leftJoin('satu_sehat_observation_ttv', function($join) {
                $join->on('satu_sehat_observation_ttv.no_rawat', '=', 'pemeriksaan_ralan.no_rawat')
                     ->on('satu_sehat_observation_ttv.tgl_perawatan', '=', 'pemeriksaan_ralan.tgl_perawatan')
                     ->on('satu_sehat_observation_ttv.jam_rawat', '=', 'pemeriksaan_ralan.jam_rawat');
            })
            ->select(
                'pemeriksaan_ralan.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.no_ktp as no_ktp_pasien',
                'pemeriksaan_ralan.tgl_perawatan',
                'pemeriksaan_ralan.jam_rawat',
                'pemeriksaan_ralan.suhu_tubuh',
                'pemeriksaan_ralan.tensi',
                'pemeriksaan_ralan.nadi',
                'pemeriksaan_ralan.respirasi',
                'pemeriksaan_ralan.tinggi',
                'pemeriksaan_ralan.berat',
                'pemeriksaan_ralan.gcs',
                'pegawai.nama as nm_petugas',
                'pegawai.no_ktp as no_ktp_petugas',
                'satu_sehat_encounter.id_encounter',
                'satu_sehat_observation_ttv.id_observation_suhu',
                'satu_sehat_observation_ttv.id_observation_tensi',
                'satu_sehat_observation_ttv.id_observation_nadi',
                'satu_sehat_observation_ttv.id_observation_respirasi'
            )
            ->whereBetween('pemeriksaan_ralan.tgl_perawatan', [$tgl1, $tgl2]);

        if ($keyword) {
            $queryRalan->where(function ($q) use ($keyword) {
                $q->where('pemeriksaan_ralan.no_rawat', 'like', "%$keyword%")
                  ->orWhere('pasien.nm_pasien', 'like', "%$keyword%");
            });
        }

        $orders = ($perPage == 'all') ? $queryRalan->paginate(1000) : $queryRalan->paginate($perPage);

        return view('satusehat.kirim_observation_ttv', compact('orders', 'tgl1', 'tgl2', 'keyword', 'perPage'));
    }

    public function post(Request $request, SatuSehatRadiologiService $service)
    {
        try {
            $data = $request->all();
            $token = $service->getAccessToken();
            $patientId = $service->getPatientId($data['no_ktp_pasien']);
            $practitionerId = $service->getPractitionerId($data['no_ktp_petugas']);

            if (!$patientId) return response()->json(['ok' => false, 'msg' => 'ID Pasien SatuSehat tidak ditemukan']);
            if (!$practitionerId) return response()->json(['ok' => false, 'msg' => 'ID Praktisi SatuSehat tidak ditemukan']);

            $results = [];
            
            // Sending Suhu Tubuh
            if (!empty($data['suhu_tubuh']) && empty($data['id_observation_suhu'])) {
                $res = $this->sendVitalSign($service, $token, [
                    'patientId' => $patientId,
                    'practitionerId' => $practitionerId,
                    'encounterId' => $data['id_encounter'],
                    'dateTime' => $data['tgl_perawatan'] . 'T' . $data['jam_rawat'] . '+07:00',
                    'code' => '8310-5',
                    'display' => 'Body temperature',
                    'value' => (float)$data['suhu_tubuh'],
                    'unit' => 'Cel',
                    'unitDisplay' => '°C'
                ]);
                if (isset($res['id'])) {
                    DB::connection('simrs')->table('satu_sehat_observation_ttv')->updateOrInsert(
                        ['no_rawat' => $data['no_rawat'], 'tgl_perawatan' => $data['tgl_perawatan'], 'jam_rawat' => $data['jam_rawat']],
                        ['id_observation_suhu' => $res['id']]
                    );
                    $results['suhu'] = $res['id'];
                }
            }

            // More vital signs can be added here (Nadi, Respirasi, etc.)
            
            return response()->json(['ok' => true, 'results' => $results]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    private function sendVitalSign($service, $token, $params)
    {
        $url = config('satusehat.base_url') . '/Observation';
        $payload = [
            "resourceType" => "Observation",
            "status" => "final",
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs"
                        ]
                    ]
                ]
            ],
            "code" => [
                "coding" => [
                    [
                        "system" => "http://loinc.org",
                        "code" => $params['code'],
                        "display" => $params['display']
                    ]
                ]
            ],
            "subject" => ["reference" => "Patient/" . $params['patientId']],
            "encounter" => ["reference" => "Encounter/" . $params['encounterId']],
            "effectiveDateTime" => $params['dateTime'],
            "issued" => $params['dateTime'],
            "performer" => [["reference" => "Practitioner/" . $params['practitionerId']]],
            "valueQuantity" => [
                "value" => $params['value'],
                "unit" => $params['unitDisplay'],
                "system" => "http://unitsofmeasure.org",
                "code" => $params['unit']
            ]
        ];

        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ],
            'json' => $payload
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
