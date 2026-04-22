<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KirimEpisodeOfCareController extends Controller
{
    public function index(Request $request)
    {
        $tgl1 = $request->get('tgl1', date('Y-m-d'));
        $tgl2 = $request->get('tgl2', date('Y-m-d'));
        $keyword = $request->get('keyword');

        $query = DB::connection('simrs')->table('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg_periksa.no_rkm_medis')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('satu_sehat_episode_of_care', 'satu_sehat_episode_of_care.no_rawat', '=', 'reg_periksa.no_rawat')
            ->select(
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.no_ktp as no_ktp_pasien',
                'satu_sehat_encounter.id_encounter',
                DB::raw('IFNULL(satu_sehat_episode_of_care.id_episode_of_care,"") as id_episode'),
                'reg_periksa.status_lanjut'
            )
            ->whereBetween('reg_periksa.tgl_registrasi', [$tgl1, $tgl2]);

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('reg_periksa.no_rawat', 'like', "%$keyword%")
                  ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$keyword%")
                  ->orWhere('pasien.nm_pasien', 'like', "%$keyword%");
            });
        }

        $orders = $query->orderBy('reg_periksa.no_rawat', 'desc')->paginate(25)->withQueryString();

        return view('satusehat.kirim_episodeofcare', compact('orders', 'tgl1', 'tgl2', 'keyword'));
    }

    public function post(Request $request)
    {
        return response()->json(['ok' => false, 'msg' => 'Fitur Pengiriman EpisodeOfCare sedang dalam tahap bridging.']);
    }
}
