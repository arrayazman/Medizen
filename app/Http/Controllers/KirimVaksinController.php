<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatuSehatRadiologiService;

class KirimVaksinController extends Controller
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

        $query = DB::connection('simrs')->table('detail_pemberian_obat')
            ->join('reg_periksa', 'reg_periksa.no_rawat', '=', 'detail_pemberian_obat.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg_periksa.no_rkm_medis')
            ->join('databarang', 'databarang.kode_brng', '=', 'detail_pemberian_obat.kode_brng')
            ->join('satu_sehat_mapping_vaksin', 'satu_sehat_mapping_vaksin.kode_brng', '=', 'detail_pemberian_obat.kode_brng')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pegawai', 'pegawai.nik', '=', 'reg_periksa.kd_dokter')
            ->leftJoin('satu_sehat_immunization', function($join) {
                $join->on('satu_sehat_immunization.no_rawat', '=', 'detail_pemberian_obat.no_rawat')
                     ->on('satu_sehat_immunization.tgl_perawatan', '=', 'detail_pemberian_obat.tgl_perawatan')
                     ->on('satu_sehat_immunization.jam', '=', 'detail_pemberian_obat.jam')
                     ->on('satu_sehat_immunization.kode_brng', '=', 'detail_pemberian_obat.kode_brng');
            })
            ->select(
                'detail_pemberian_obat.no_rawat',
                'detail_pemberian_obat.tgl_perawatan',
                'detail_pemberian_obat.jam',
                'detail_pemberian_obat.kode_brng',
                'detail_pemberian_obat.no_batch',
                'databarang.nama_brng as nm_brng',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                DB::raw('IFNULL(pasien.no_ktp,"") as no_ktp_pasien'),
                'pegawai.nama as nm_dokter',
                'pegawai.no_ktp as no_ktp_dokter',
                'satu_sehat_encounter.id_encounter',
                'databarang.expire',
                'satu_sehat_mapping_vaksin.vaksin_code',
                'satu_sehat_mapping_vaksin.vaksin_system',
                'satu_sehat_mapping_vaksin.vaksin_display',
                'satu_sehat_mapping_vaksin.route_code',
                'satu_sehat_mapping_vaksin.route_system',
                'satu_sehat_mapping_vaksin.route_display',
                'satu_sehat_mapping_vaksin.dose_quantity_code',
                'satu_sehat_mapping_vaksin.dose_quantity_system',
                'satu_sehat_mapping_vaksin.dose_quantity_unit',
                DB::raw('IFNULL(satu_sehat_immunization.id_immunization,"") as id_vaksin')
            )
            ->whereBetween('detail_pemberian_obat.tgl_perawatan', [$tgl1, $tgl2]);

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('detail_pemberian_obat.no_rawat', 'like', "%$keyword%")
                  ->orWhere('pasien.nm_pasien', 'like', "%$keyword%")
                  ->orWhere('databarang.nama_brng', 'like', "%$keyword%");
            });
        }

        $orders = $query->orderBy('detail_pemberian_obat.tgl_perawatan', 'desc')
                        ->orderBy('detail_pemberian_obat.jam', 'desc')
                        ->paginate(25)->withQueryString();

        return view('satusehat.kirim_vaksin', compact('orders', 'tgl1', 'tgl2', 'keyword'));
    }

    public function post(Request $request)
    {
        $logs = [];
        try {
            $no_rawat = $request->no_rawat;
            $tgl_perawatan = $request->tgl_perawatan;
            $jam = $request->jam;
            $kode_brng = $request->kode_brng;

            // 1. Get Patient ID
            $logs[] = ['type' => 'info', 'msg' => "Mencari Patient ID untuk NIK: {$request->no_ktp_pasien}"];
            $patientId = $this->ssService->getPatientId($request->no_ktp_pasien);
            if (!$patientId) {
                return response()->json(['ok' => false, 'msg' => 'Patient ID tidak ditemukan di SATUSEHAT. Pastikan NIK Pasien valid.', 'logs' => $logs]);
            }
            $logs[] = ['type' => 'ok', 'msg' => "Patient ID ditemukan: $patientId"];

            // 2. Get Practitioner ID
            $logs[] = ['type' => 'info', 'msg' => "Mencari Practitioner ID untuk NIK: {$request->no_ktp_dokter}"];
            $practitionerId = $this->ssService->getPractitionerId($request->no_ktp_dokter);
            if (!$practitionerId) {
                return response()->json(['ok' => false, 'msg' => 'Practitioner ID tidak ditemukan di SATUSEHAT. Pastikan NIK Dokter valid.', 'logs' => $logs]);
            }
            $logs[] = ['type' => 'ok', 'msg' => "Practitioner ID ditemukan: $practitionerId"];

            // 3. Validation & Construction (AUTO-FIX VERSION)
            $datetime = $tgl_perawatan . 'T' . $jam . '+07:00';
            $occDateOnly = date('Y-m-d', strtotime($tgl_perawatan));
            
            $expDateInput = $request->expire ? date('Y-m-d', strtotime($request->expire)) : null;
            
            // AUTO-FIX: expirationDate cannot be before occurrenceDate
            if (!$expDateInput || $expDateInput < $occDateOnly || $expDateInput == '0000-00-00') {
                $expirationDate = date('Y-m-d', strtotime($occDateOnly . ' +1 year'));
                $logs[] = ['type' => 'info', 'msg' => 'AUTO-FIX: Mengatur ExpirationDate +1 Tahun karena data kosong/invalid.'];
            } else {
                $expirationDate = $expDateInput;
            }

            $payload = [
                "resourceType" => "Immunization",
                "status" => "completed",
                "vaccineCode" => [
                    "coding" => [
                        [
                            "system" => $request->vaksin_system,
                            "code" => $request->vaksin_code,
                            "display" => $request->vaksin_display
                        ]
                    ]
                ],
                "patient" => [
                    "reference" => "Patient/" . $patientId,
                    "display" => $request->nm_pasien
                ],
                "encounter" => [
                    "reference" => "Encounter/" . $request->id_encounter
                ],
                "occurrenceDateTime" => $datetime,
                "recorded" => $datetime,
                "primarySource" => true,
                "lotNumber" => ($request->no_batch && $request->no_batch != '-') ? $request->no_batch : "BATCH-".date('Ymd'),
                "expirationDate" => $expirationDate,
                "performer" => [
                    [
                        // SATUSEHAT Requires role coding AP (Administering Provider)
                        "function" => [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/v2-0443",
                                    "code" => "AP",
                                    "display" => "Administering Provider"
                                ]
                            ]
                        ],
                        "actor" => [
                            "reference" => "Practitioner/" . $practitionerId,
                            "display" => $request->nm_dokter
                        ]
                    ]
                ],
                "route" => [
                    "coding" => [
                        [
                            "system" => $request->route_system,
                            "code" => $request->route_code,
                            "display" => $request->route_display
                        ]
                    ]
                ],
                "doseQuantity" => [
                    "value" => (float)$request->dose_quantity_code,
                    "unit" => $request->dose_quantity_unit,
                    "system" => $request->dose_quantity_system,
                    "code" => $request->dose_quantity_code
                ],
                "reasonCode" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "140100005",
                                "display" => "Routine immunization"
                            ]
                        ]
                    ]
                ],
                "protocolApplied" => [
                    [
                        "doseNumberPositiveInt" => 1,
                        "targetDisease" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => $request->vaksin_code,
                                        "display" => $request->vaksin_display
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $logs[] = ['type' => 'info', 'msg' => "Mengirim resource Immunization ke SATUSEHAT..."];
            
            // Check if already sent (UPDATE)
            $existing = DB::connection('simrs')->table('satu_sehat_immunization')
                ->where('no_rawat', $no_rawat)
                ->where('tgl_perawatan', $tgl_perawatan)
                ->where('jam', $jam)
                ->where('kode_brng', $kode_brng)
                ->first();

            $res = null;
            if ($existing && $existing->id_immunization) {
                $payload['id'] = $existing->id_immunization;
                $res = $this->ssService->sendResource('Immunization', $payload, $existing->id_immunization);
                $logs[] = ['type' => 'ok', 'msg' => "Resource Immunization berhasil diperbarui."];
            } else {
                $res = $this->ssService->sendResource('Immunization', $payload);
                $logs[] = ['type' => 'ok', 'msg' => "Resource Immunization berhasil dikirim."];
            }

            $id_immunization = $res['id'];

            // 4. Save to Database
            if ($existing) {
                DB::connection('simrs')->table('satu_sehat_immunization')
                    ->where('no_rawat', $no_rawat)
                    ->where('tgl_perawatan', $tgl_perawatan)
                    ->where('jam', $jam)
                    ->where('kode_brng', $kode_brng)
                    ->update([
                        'id_immunization' => $id_immunization
                    ]);
            } else {
                DB::connection('simrs')->table('satu_sehat_immunization')->insert([
                    'no_rawat' => $no_rawat,
                    'tgl_perawatan' => $tgl_perawatan,
                    'jam' => $jam,
                    'kode_brng' => $kode_brng,
                    'no_batch' => $request->no_batch ?: "-",
                    'no_faktur' => "-",
                    'id_immunization' => $id_immunization
                ]);
            }

            return response()->json([
                'ok' => true, 
                'id_vaksin' => $id_immunization, 
                'logs' => $logs
            ]);

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            
            // Try to parse OperationOutcome if it's an API Error
            if (str_contains($msg, 'API Error:')) {
                $jsonStr = str_replace('API Error: ', '', $msg);
                $data = json_decode($jsonStr, true);
                if (isset($data['issue'])) {
                    $issues = [];
                    foreach($data['issue'] as $is) {
                        $issues[] = ($is['details']['text'] ?? 'Unknown API Error');
                    }
                    $msg = "SATUSEHAT: " . implode(' | ', $issues);
                }
            }

            $logs[] = ['type' => 'err', 'msg' => $msg];
            return response()->json(['ok' => false, 'msg' => $msg, 'logs' => $logs]);
        }
    }
}
