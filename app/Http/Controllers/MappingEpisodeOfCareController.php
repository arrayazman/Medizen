<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MappingEpisodeOfCareController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');

        $query = DB::connection('simrs')->table('satu_sehat_mapping_episode_of_care')
            ->select('*');

        if ($keyword) {
            $query->where('nama_episode', 'like', "%$keyword%");
        }

        $mappings = $query->paginate(25)->withQueryString();

        return view('satusehat.mapping_episodeofcare', compact('mappings', 'keyword'));
    }

    public function post(Request $request)
    {
        // CRUD for mapping
        return response()->json(['ok' => false, 'msg' => 'Fitur Mapping EpisodeOfCare sedang dalam tahap bridging.']);
    }
}
