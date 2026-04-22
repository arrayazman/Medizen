<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MappingEpisodeOfCareController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');

        $query = DB::connection('simrs')->table('satu_sehat_mapping_diagnosa_episode')
            ->join('penyakit', 'penyakit.kd_penyakit', '=', 'satu_sehat_mapping_diagnosa_episode.kd_penyakit')
            ->join('satu_sehat_ref_episodeofcare_type', 'satu_sehat_ref_episodeofcare_type.kode', '=', 'satu_sehat_mapping_diagnosa_episode.kode_episode')
            ->select(
                'satu_sehat_mapping_diagnosa_episode.kd_penyakit',
                'penyakit.nm_penyakit',
                'satu_sehat_mapping_diagnosa_episode.kode_episode',
                'satu_sehat_ref_episodeofcare_type.display as display_episode',
                'satu_sehat_ref_episodeofcare_type.keterangan'
            );

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('satu_sehat_mapping_diagnosa_episode.kd_penyakit', 'like', "%$keyword%")
                  ->orWhere('penyakit.nm_penyakit', 'like', "%$keyword%")
                  ->orWhere('satu_sehat_ref_episodeofcare_type.display', 'like', "%$keyword%");
            });
        }

        $mappings = $query->paginate(25)->withQueryString();
        $episodeTypes = DB::connection('simrs')->table('satu_sehat_ref_episodeofcare_type')->orderBy('display')->get();

        return view('satusehat.mapping_episodeofcare', compact('mappings', 'episodeTypes', 'keyword'));
    }

    public function post(Request $request)
    {
        try {
            $request->validate([
                'kd_penyakit' => 'required',
                'kode_episode' => 'required',
            ]);

            DB::connection('simrs')->table('satu_sehat_mapping_diagnosa_episode')->updateOrInsert(
                ['kd_penyakit' => $request->kd_penyakit],
                ['kode_episode' => $request->kode_episode]
            );

            return response()->json(['ok' => true, 'msg' => 'Mapping Diagnosa ke Episode Of Care berhasil disimpan.']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => 'Gagal menyimpan mapping: ' . $e->getMessage()]);
        }
    }

    public function destroy(Request $request)
    {
        try {
            DB::connection('simrs')->table('satu_sehat_mapping_diagnosa_episode')
                ->where('kd_penyakit', $request->kd_penyakit)
                ->delete();

            return response()->json(['ok' => true, 'msg' => 'Mapping berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => 'Gagal menghapus mapping: ' . $e->getMessage()]);
        }
    }

    public function searchPenyakit(Request $request)
    {
        $q = $request->get('q');
        $data = DB::connection('simrs')->table('penyakit')
            ->where('kd_penyakit', 'like', "%$q%")
            ->orWhere('nm_penyakit', 'like', "%$q%")
            ->limit(20)
            ->get();
        return response()->json($data);
    }
}
