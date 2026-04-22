<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MappingOrganisasiController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $perPage = $request->get('per_page', 25);

        $query = DB::connection('simrs')->table('departemen')
            ->leftJoin('satu_sehat_mapping_departemen', 'departemen.dep_id', '=', 'satu_sehat_mapping_departemen.dep_id')
            ->select('departemen.dep_id', 'departemen.nama', 'satu_sehat_mapping_departemen.id_organisasi_satusehat');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('departemen.nama', 'like', "%$keyword%")->orWhere('departemen.dep_id', 'like', "%$keyword%");
            });
        }

        $mappings = ($perPage == 'all') ? $query->paginate(1000) : $query->paginate($perPage);

        return view('satusehat.mapping_organisasi', compact('mappings', 'keyword', 'perPage'));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            
            DB::connection('simrs')->table('satu_sehat_mapping_departemen')->updateOrInsert(
                ['dep_id' => $data['dep_id']],
                ['id_organisasi_satusehat' => $data['id_fhir']]
            );

            return response()->json(['ok' => true, 'msg' => 'Mapping Organisasi berhasil disimpan']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }
}
