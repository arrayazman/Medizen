<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatuSehatRadiologiService;

class KirimMedicationController extends Controller
{
    public function index(Request $request)
    {
        $tgl1 = $request->get('tgl1', date('Y-m-d'));
        $tgl2 = $request->get('tgl2', date('Y-m-d'));
        $keyword = $request->get('keyword');

        // Query Ralan
        $ralan = DB::connection('simrs')->table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('resep_obat', 'reg_periksa.no_rawat', '=', 'resep_obat.no_rawat')
            ->join('pegawai', 'resep_obat.kd_dokter', '=', 'pegawai.nik')
            ->join('satu_sehat_medicationrequest', 'satu_sehat_medicationrequest.no_resep', '=', 'resep_obat.no_resep')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('detail_pemberian_obat', function($join) {
                $join->on('detail_pemberian_obat.no_rawat', '=', 'resep_obat.no_rawat')
                     ->on('detail_pemberian_obat.tgl_perawatan', '=', 'resep_obat.tgl_perawatan')
                     ->on('detail_pemberian_obat.jam', '=', 'resep_obat.jam');
            })
            ->join('aturan_pakai', function($join) {
                $join->on('aturan_pakai.no_rawat', '=', 'detail_pemberian_obat.no_rawat')
                     ->on('aturan_pakai.tgl_perawatan', '=', 'detail_pemberian_obat.tgl_perawatan')
                     ->on('aturan_pakai.jam', '=', 'detail_pemberian_obat.jam')
                     ->on('aturan_pakai.kode_brng', '=', 'detail_pemberian_obat.kode_brng');
            })
            ->join('satu_sehat_mapping_obat', 'satu_sehat_mapping_obat.kode_brng', '=', 'detail_pemberian_obat.kode_brng')
            ->join('bangsal', 'bangsal.kd_bangsal', '=', 'detail_pemberian_obat.kd_bangsal')
            ->join('satu_sehat_mapping_lokasi_depo_farmasi', 'satu_sehat_mapping_lokasi_depo_farmasi.kd_bangsal', '=', 'bangsal.kd_bangsal')
            ->join('satu_sehat_medication', 'satu_sehat_medication.kode_brng', '=', 'satu_sehat_mapping_obat.kode_brng')
            ->join('mutasi_berkas', 'mutasi_berkas.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('satu_sehat_medicationdispense', function($join) {
                $join->on('satu_sehat_medicationdispense.no_rawat', '=', 'detail_pemberian_obat.no_rawat')
                     ->on('satu_sehat_medicationdispense.tgl_perawatan', '=', 'detail_pemberian_obat.tgl_perawatan')
                     ->on('satu_sehat_medicationdispense.jam', '=', 'detail_pemberian_obat.jam')
                     ->on('satu_sehat_medicationdispense.kode_brng', '=', 'detail_pemberian_obat.kode_brng')
                     ->on('satu_sehat_medicationdispense.no_batch', '=', 'detail_pemberian_obat.no_batch')
                     ->on('satu_sehat_medicationdispense.no_faktur', '=', 'detail_pemberian_obat.no_faktur');
            })
            ->select(
                'reg_periksa.tgl_registrasi', 'reg_periksa.jam_reg', 'reg_periksa.no_rawat', 'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien', 'pasien.no_ktp as no_ktp_pasien', 'pegawai.nama as nama_dokter', 'pegawai.no_ktp as no_ktp_praktisi',
                'satu_sehat_encounter.id_encounter', 'satu_sehat_mapping_obat.obat_code', 'satu_sehat_mapping_obat.obat_system',
                'detail_pemberian_obat.kode_brng', 'satu_sehat_mapping_obat.obat_display', 'satu_sehat_mapping_obat.form_code',
                'satu_sehat_mapping_obat.form_system', 'satu_sehat_mapping_obat.form_display', 'satu_sehat_mapping_obat.route_code',
                'satu_sehat_mapping_obat.route_system', 'satu_sehat_mapping_obat.route_display', 'satu_sehat_mapping_obat.denominator_code',
                'satu_sehat_mapping_obat.denominator_system', 'resep_obat.tgl_peresepan', 'resep_obat.jam_peresepan',
                'detail_pemberian_obat.jml', 'satu_sehat_medication.id_medication', 'aturan_pakai.aturan', 'resep_obat.no_resep',
                DB::raw("IFNULL(satu_sehat_medicationdispense.id_medicationdispanse,'') as id_medication_dispense"),
                'detail_pemberian_obat.no_batch', 'detail_pemberian_obat.no_faktur', 'detail_pemberian_obat.tgl_perawatan',
                'detail_pemberian_obat.jam as jam_beri', 'satu_sehat_mapping_lokasi_depo_farmasi.id_lokasi_satusehat',
                'bangsal.nm_bangsal', 'satu_sehat_medicationrequest.id_medicationrequest',
                DB::raw("'Ralan' as status_lanjut")
            )
            ->whereBetween('mutasi_berkas.kembali', [$tgl1, $tgl2]);

        // Query Ranap
        $ranap = DB::connection('simrs')->table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('resep_obat', 'reg_periksa.no_rawat', '=', 'resep_obat.no_rawat')
            ->join('pegawai', 'resep_obat.kd_dokter', '=', 'pegawai.nik')
            ->join('satu_sehat_medicationrequest', 'satu_sehat_medicationrequest.no_resep', '=', 'resep_obat.no_resep')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('detail_pemberian_obat', function($join) {
                $join->on('detail_pemberian_obat.no_rawat', '=', 'resep_obat.no_rawat')
                     ->on('detail_pemberian_obat.tgl_perawatan', '=', 'resep_obat.tgl_perawatan')
                     ->on('detail_pemberian_obat.jam', '=', 'resep_obat.jam');
            })
            ->join('aturan_pakai', function($join) {
                $join->on('aturan_pakai.no_rawat', '=', 'detail_pemberian_obat.no_rawat')
                     ->on('aturan_pakai.tgl_perawatan', '=', 'detail_pemberian_obat.tgl_perawatan')
                     ->on('aturan_pakai.jam', '=', 'detail_pemberian_obat.jam')
                     ->on('aturan_pakai.kode_brng', '=', 'detail_pemberian_obat.kode_brng');
            })
            ->join('satu_sehat_mapping_obat', 'satu_sehat_mapping_obat.kode_brng', '=', 'detail_pemberian_obat.kode_brng')
            ->join('bangsal', 'bangsal.kd_bangsal', '=', 'detail_pemberian_obat.kd_bangsal')
            ->join('satu_sehat_mapping_lokasi_depo_farmasi', 'satu_sehat_mapping_lokasi_depo_farmasi.kd_bangsal', '=', 'bangsal.kd_bangsal')
            ->join('satu_sehat_medication', 'satu_sehat_medication.kode_brng', '=', 'satu_sehat_mapping_obat.kode_brng')
            ->join('kamar_inap', 'kamar_inap.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('satu_sehat_medicationdispense', function($join) {
                $join->on('satu_sehat_medicationdispense.no_rawat', '=', 'detail_pemberian_obat.no_rawat')
                     ->on('satu_sehat_medicationdispense.tgl_perawatan', '=', 'detail_pemberian_obat.tgl_perawatan')
                     ->on('satu_sehat_medicationdispense.jam', '=', 'detail_pemberian_obat.jam')
                     ->on('satu_sehat_medicationdispense.kode_brng', '=', 'detail_pemberian_obat.kode_brng')
                     ->on('satu_sehat_medicationdispense.no_batch', '=', 'detail_pemberian_obat.no_batch')
                     ->on('satu_sehat_medicationdispense.no_faktur', '=', 'detail_pemberian_obat.no_faktur');
            })
            ->select(
                'reg_periksa.tgl_registrasi', 'reg_periksa.jam_reg', 'reg_periksa.no_rawat', 'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien', 'pasien.no_ktp as no_ktp_pasien', 'pegawai.nama as nama_dokter', 'pegawai.no_ktp as no_ktp_praktisi',
                'satu_sehat_encounter.id_encounter', 'satu_sehat_mapping_obat.obat_code', 'satu_sehat_mapping_obat.obat_system',
                'detail_pemberian_obat.kode_brng', 'satu_sehat_mapping_obat.obat_display', 'satu_sehat_mapping_obat.form_code',
                'satu_sehat_mapping_obat.form_system', 'satu_sehat_mapping_obat.form_display', 'satu_sehat_mapping_obat.route_code',
                'satu_sehat_mapping_obat.route_system', 'satu_sehat_mapping_obat.route_display', 'satu_sehat_mapping_obat.denominator_code',
                'satu_sehat_mapping_obat.denominator_system', 'resep_obat.tgl_peresepan', 'resep_obat.jam_peresepan',
                'detail_pemberian_obat.jml', 'satu_sehat_medication.id_medication', 'aturan_pakai.aturan', 'resep_obat.no_resep',
                DB::raw("IFNULL(satu_sehat_medicationdispense.id_medicationdispanse,'') as id_medication_dispense"),
                'detail_pemberian_obat.no_batch', 'detail_pemberian_obat.no_faktur', 'detail_pemberian_obat.tgl_perawatan',
                'detail_pemberian_obat.jam as jam_beri', 'satu_sehat_mapping_lokasi_depo_farmasi.id_lokasi_satusehat',
                'bangsal.nm_bangsal', 'satu_sehat_medicationrequest.id_medicationrequest',
                DB::raw("'Ranap' as status_lanjut")
            )
            ->whereBetween('kamar_inap.tgl_keluar', [$tgl1, $tgl2]);

        if ($keyword) {
            $filter = function($q) use ($keyword) {
                $q->where('reg_periksa.no_rawat', 'like', "%$keyword%")
                  ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$keyword%")
                  ->orWhere('pasien.nm_pasien', 'like', "%$keyword%")
                  ->orWhere('satu_sehat_mapping_obat.obat_display', 'like', "%$keyword%");
            };
            $ralan->where($filter);
            $ranap->where($filter);
        }

        $perPage = $request->get('per_page', 25);
        if ($perPage == 'all') {
            $perPage = 1000000;
        }

        $orders = $ralan->union($ranap)
                        ->orderBy('no_rawat', 'desc')
                        ->paginate($perPage)
                        ->withQueryString();

        return view('satusehat.kirim_medication', compact('orders', 'tgl1', 'tgl2', 'keyword', 'perPage'));
    }

    public function post(Request $request, SatuSehatRadiologiService $ssService)
    {
        $logs = [];
        $addLog = function ($type, $msg) use (&$logs) { $logs[] = ['type' => $type, 'msg' => $msg]; };

        try {
            $data = $request->all();
            
            if (empty($data['id_medicationrequest'])) throw new \Exception('MedicationRequest ID belum ada (Resep belum terkirim).');
            if (empty($data['id_medication'])) throw new \Exception('Medication ID belum ada (Master Obat belum dimapping).');
            if (empty($data['id_encounter'])) throw new \Exception('Encounter ID belum ada (Pasien belum registrasi SatuSehat).');
            if (empty($data['id_lokasi_satusehat'])) throw new \Exception('ID Lokasi Depo Farmasi belum ada.');

            $addLog('info', 'MENGAMBIL ID FHIR PASIEN & PRAKTISI...');
            
            $idPasien = $ssService->getPatientId($data['no_ktp_pasien']);
            if (!$idPasien) throw new \Exception('Patient ID tidak ditemukan. NIK: ' . $data['no_ktp_pasien']);
            
            $idDokter = $ssService->getPractitionerId($data['no_ktp_praktisi']);
            if (!$idDokter) throw new \Exception('Practitioner ID (Dokter) tidak ditemukan. NIK: ' . $data['no_ktp_praktisi']);

            $orgId = $ssService->getOrganizationId();
            $effectiveDateTime = \Carbon\Carbon::parse($data['tgl_perawatan'] . ' ' . $data['jam_beri'])->toIso8601String();

            $payload = [
                'resourceType' => 'MedicationDispense',
                'status' => 'completed',
                'medicationReference' => [
                    'reference' => "Medication/{$data['id_medication']}",
                    'display' => $data['obat_display']
                ],
                'subject' => [
                    'reference' => "Patient/{$idPasien}"
                ],
                'context' => [
                    'reference' => "Encounter/{$data['id_encounter']}"
                ],
                'performer' => [
                    [
                        'actor' => [
                            'reference' => "Practitioner/{$idDokter}"
                        ]
                    ]
                ],
                'authorizingPrescription' => [
                    [
                        'reference' => "MedicationRequest/{$data['id_medicationrequest']}"
                    ]
                ],
                'location' => [
                    'reference' => "Location/{$data['id_lokasi_satusehat']}",
                    'display' => $data['nm_bangsal']
                ],
                'quantity' => [
                    'value' => (float)$data['jml'],
                    'system' => "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                    'code' => $data['denominator_code'] ?: 'TAB'
                ],
                'whenHandedOver' => $effectiveDateTime,
                'dosageInstruction' => [
                    [
                        'text' => $data['aturan'],
                        'route' => [
                            'coding' => [
                                [
                                    'system' => $data['route_system'] ?: 'http://www.whocc.no/atc',
                                    'code' => $data['route_code'] ?: 'oral',
                                    'display' => $data['route_display'] ?: 'Oral'
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            if ($data['id_medication_dispense']) {
                $payload['id'] = $data['id_medication_dispense'];
            } else {
                $payload['identifier'] = [
                    [
                        'system' => "http://sys-ids.kemkes.go.id/medicationdispense/{$orgId}",
                        'value' => "{$data['no_resep']}.{$data['kode_brng']}"
                    ]
                ];
            }

            $isUpdate = !empty($data['id_medication_dispense']);
            $addLog('info', ($isUpdate ? 'UPDATE' : 'KIRIM') . ' RESOURCE MEDICATION DISPENSE...');

            $res = $ssService->sendResource('MedicationDispense', $payload, $data['id_medication_dispense'] ?: null);
            
            $fhirId = $res['id'];
            $addLog('ok', "BERHASIL DISIMPAN: " . $fhirId);

            // Simpan ke SIMRS
            $this->saveToSimrs($data, $fhirId);

            return response()->json(['ok' => true, 'id_medication_dispense' => $fhirId, 'logs' => $logs]);

        } catch (\Exception $e) {
            $addLog('err', $e->getMessage());
            return response()->json(['ok' => false, 'msg' => $e->getMessage(), 'logs' => $logs]);
        }
    }

    private function saveToSimrs($data, $fhirId)
    {
        DB::connection('simrs')->table('satu_sehat_medicationdispense')->updateOrInsert(
            [
                'no_rawat' => $data['no_rawat'],
                'tgl_perawatan' => $data['tgl_perawatan'],
                'jam' => $data['jam_beri'],
                'kode_brng' => $data['kode_brng'],
                'no_batch' => $data['no_batch'],
                'no_faktur' => $data['no_faktur']
            ],
            [
                'id_medicationdispanse' => $fhirId
            ]
        );
    }
}
