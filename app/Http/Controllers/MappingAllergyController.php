<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MappingAllergyController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');

        $query = DB::connection('simrs')->table('satu_sehat_mapping_allergy');

        if ($keyword) {
            $query->where('keyword', 'like', "%$keyword%")
                  ->orWhere('display', 'like', "%$keyword%");
        }

        $mappings = $query->orderBy('keyword', 'asc')->paginate(25)->withQueryString();

        return view('satusehat.mapping_allergy', compact('mappings', 'keyword'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'keyword' => 'required',
            'category' => 'required',
            'code' => 'required',
            'display' => 'required',
        ]);

        try {
            DB::connection('simrs')->table('satu_sehat_mapping_allergy')->updateOrInsert(
                ['keyword' => $request->keyword],
                [
                    'category' => $request->category,
                    'code' => $request->code,
                    'display' => $request->display,
                    'system' => $request->system ?: 'http://snomed.info/sct',
                ]
            );
            return response()->json(['ok' => true, 'msg' => 'Mapping berhasil disimpan.']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => 'Gagal menyimpan mapping: ' . $e->getMessage()]);
        }
    }

    public function destroy(Request $request)
    {
        try {
            DB::connection('simrs')->table('satu_sehat_mapping_allergy')->where('id', $request->id)->delete();
            return response()->json(['ok' => true, 'msg' => 'Mapping berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => 'Gagal menghapus mapping: ' . $e->getMessage()]);
        }
    }
}
