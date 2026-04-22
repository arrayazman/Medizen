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

        $orders = $ralan->union($ranap)
                        ->orderBy('no_rawat', 'desc')
                        ->paginate(25)
                        ->withQueryString();

        return view('satusehat.kirim_medication', compact('orders', 'tgl1', 'tgl2', 'keyword'));
    }

    public function post(Request $request, SatuSehatRadiologiService $satusehatService)
    {
        return response()->json(['ok' => false, 'msg' => 'Metode POST sedang disiapkan mengikuti struktur MedicationDispense.', 'logs' => []]);
    }
}
