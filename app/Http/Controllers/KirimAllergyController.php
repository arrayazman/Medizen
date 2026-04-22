<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatuSehatRadiologiService;
use Carbon\Carbon;

class KirimAllergyController extends Controller
{
    public function index(Request $request)
    {
        $tgl1 = $request->get('tgl1', date('Y-m-d'));
        $tgl2 = $request->get('tgl2', date('Y-m-d'));
        $keyword = $request->get('keyword');

        // Query Ralan
        $ralan = DB::connection('simrs')->table('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg_periksa.no_rkm_medis')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pemeriksaan_ralan', 'pemeriksaan_ralan.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pegawai', 'pegawai.nik', '=', 'pemeriksaan_ralan.nip')
            ->leftJoin('satu_sehat_allergy_intolerance', function($join) {
                $join->on('satu_sehat_allergy_intolerance.no_rawat', '=', 'pemeriksaan_ralan.no_rawat')
                     ->on('satu_sehat_allergy_intolerance.tgl_perawatan', '=', 'pemeriksaan_ralan.tgl_perawatan')
                     ->on('satu_sehat_allergy_intolerance.jam_rawat', '=', 'pemeriksaan_ralan.jam_rawat');
            })
            ->select(
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.no_ktp as no_ktp_pasien',
                'satu_sehat_encounter.id_encounter',
                'pemeriksaan_ralan.alergi',
                'pegawai.nama as nama_praktisi',
                'pegawai.no_ktp as ktp_praktisi',
                'pemeriksaan_ralan.tgl_perawatan',
                'pemeriksaan_ralan.jam_rawat',
                DB::raw('IFNULL(satu_sehat_allergy_intolerance.id_allergy_intolerance,"") as id_allergy'),
                DB::raw("'Ralan' as status_lanjut")
            )
            ->where('pemeriksaan_ralan.alergi', '<>', '')
            ->whereBetween('reg_periksa.tgl_registrasi', [$tgl1, $tgl2]);

        // Query Ranap
        $ranap = DB::connection('simrs')->table('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg_periksa.no_rkm_medis')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pemeriksaan_ranap', 'pemeriksaan_ranap.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pegawai', 'pegawai.nik', '=', 'pemeriksaan_ranap.nip')
            ->leftJoin('satu_sehat_allergy_intolerance', function($join) {
                $join->on('satu_sehat_allergy_intolerance.no_rawat', '=', 'pemeriksaan_ranap.no_rawat')
                     ->on('satu_sehat_allergy_intolerance.tgl_perawatan', '=', 'pemeriksaan_ranap.tgl_perawatan')
                     ->on('satu_sehat_allergy_intolerance.jam_rawat', '=', 'pemeriksaan_ranap.jam_rawat');
            })
            ->select(
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.no_ktp as no_ktp_pasien',
                'satu_sehat_encounter.id_encounter',
                'pemeriksaan_ranap.alergi',
                'pegawai.nama as nama_praktisi',
                'pegawai.no_ktp as ktp_praktisi',
                'pemeriksaan_ranap.tgl_perawatan',
                'pemeriksaan_ranap.jam_rawat',
                DB::raw('IFNULL(satu_sehat_allergy_intolerance.id_allergy_intolerance,"") as id_allergy'),
                DB::raw("'Ranap' as status_lanjut")
            )
            ->where('pemeriksaan_ranap.alergi', '<>', '')
            ->whereBetween('reg_periksa.tgl_registrasi', [$tgl1, $tgl2]);

        if ($keyword) {
            $filter = function($q) use ($keyword) {
                $q->where('reg_periksa.no_rawat', 'like', "%$keyword%")
                  ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$keyword%")
                  ->orWhere('pasien.nm_pasien', 'like', "%$keyword%")
                  ->orWhere('pegawai.nama', 'like', "%$keyword%");
            };
            $ralan->where($filter);
            $ranap->where($filter);
        }

        $orders = $ralan->union($ranap)
                        ->orderBy('no_rawat', 'desc')
                        ->paginate(25)
                        ->withQueryString();

        return view('satusehat.kirim_allergy', compact('orders', 'tgl1', 'tgl2', 'keyword'));
    }

    public function post(Request $request, SatuSehatRadiologiService $satusehatService)
    {
        $logs = [];
        $logs[] = ['type' => 'info', 'msg' => 'MENGANALISA DATA ALERGI...'];

        try {
            $no_rawat = $request->get('no_rawat');
            $alergi_txt = $request->get('alergi');
            $nik_pasien = $request->get('no_ktp_pasien');
            $nik_praktisi = $request->get('ktp_praktisi');
            $id_encounter = $request->get('id_encounter');
            $tgl_rawat = $request->get('tgl_perawatan');
            $jam_rawat = $request->get('jam_rawat');
            $id_allergy_existing = $request->get('id_allergy');

            if (!$id_encounter) {
                throw new \Exception("ID Encounter tidak ditemukan. Kirim data kunjungan terlebih dahulu.");
            }

            // 1. Get Patient ID
            $logs[] = ['type' => 'info', 'msg' => "MENCARI ID PASIEN (NIK: $nik_pasien)..."];
            $id_patient = $satusehatService->getPatientId($nik_pasien);
            if (!$id_patient) throw new \Exception("ID Pasien tidak ditemukan di SATUSEHAT.");

            // 2. Get Practitioner ID
            $logs[] = ['type' => 'info', 'msg' => "MENCARI ID PRAKTISI (NIK: $nik_praktisi)..."];
            $id_practitioner = $satusehatService->getPractitionerId($nik_praktisi);
            if (!$id_practitioner) throw new \Exception("ID Praktisi tidak ditemukan di SATUSEHAT.");

            // 3. Mapping Logic (Database based)
            $mapping = DB::connection('simrs')->table('satu_sehat_mapping_allergy')
                ->whereRaw('? LIKE CONCAT("%", keyword, "%")', [$alergi_txt])
                ->first();

            if (!$mapping) {
                throw new \Exception("Alergi '$alergi_txt' belum di-map ke SNOMED-CT. Silakan lakukan mapping terlebih dahulu.");
            }

            $category = $mapping->category;
            $code = $mapping->code;
            $display = $mapping->display;
            $system = $mapping->system;

            // 4. Build JSON
            $payload = [
                "resourceType" => "AllergyIntolerance",
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/allergy/" . $satusehatService->getOrganizationId(),
                        "value" => $no_rawat
                    ]
                ],
                "clinicalStatus" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical",
                            "code" => "active",
                            "display" => "Active"
                        ]
                    ]
                ],
                "verificationStatus" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/allergyintolerance-verification",
                            "code" => "confirmed",
                            "display" => "Confirmed"
                        ]
                    ]
                ],
                "category" => [$category],
                "code" => [
                    "coding" => [
                        [
                            "system" => $system,
                            "code" => $code,
                            "display" => $display
                        ]
                    ],
                    "text" => $alergi_txt
                ],
                "patient" => [
                    "reference" => "Patient/" . $id_patient,
                    "display" => $request->get('nm_pasien')
                ],
                "encounter" => [
                    "reference" => "Encounter/" . $id_encounter,
                    "display" => "Kunjungan " . $request->get('nm_pasien') . " pada " . $tgl_rawat
                ],
                "recordedDate" => Carbon::parse($tgl_rawat . ' ' . $jam_rawat)->format('Y-m-d\TH:i:sP'),
                "recorder" => [
                    "reference" => "Practitioner/" . $id_practitioner,
                    "display" => $request->get('nama_praktisi')
                ]
            ];

            // 5. Send to SatuSehat
            $logs[] = ['type' => 'info', 'msg' => "MENGIRIM RESOURCE ALLERGYINTOLERANCE..."];
            $result = $satusehatService->sendResource('AllergyIntolerance', $payload, $id_allergy_existing ?: null);
            
            $fhir_id = $result['id'];

            // 6. Save to local DB
            DB::connection('simrs')->table('satu_sehat_allergy_intolerance')->updateOrInsert(
                [
                    'no_rawat' => $no_rawat,
                    'tgl_perawatan' => $tgl_rawat,
                    'jam_rawat' => $jam_rawat
                ],
                [
                    'status' => 'Rencana Perawatan',
                    'id_allergy_intolerance' => $fhir_id
                ]
            );

            $logs[] = ['type' => 'ok', 'msg' => "BERHASIL DIKIRIM (ID: $fhir_id)"];
            return response()->json([
                'ok' => true, 
                'msg' => 'Data Alergi berhasil dikirim.', 
                'id_allergy' => $fhir_id,
                'logs' => $logs
            ]);

        } catch (\Exception $e) {
            $logs[] = ['type' => 'err', 'msg' => "ERROR: " . $e->getMessage()];
            return response()->json([
                'ok' => false, 
                'msg' => $e->getMessage(), 
                'logs' => $logs
            ]);
        }
    }

}
