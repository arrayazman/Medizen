<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MappingLokasiController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'poli');
        $keyword = $request->get('keyword', '');
        $perPage = $request->get('per_page', 25);

        if ($type == 'poli') {
            $query = DB::connection('simrs')->table('poliklinik')
                ->leftJoin('satu_sehat_mapping_lokasi_poli', 'poliklinik.kd_poli', '=', 'satu_sehat_mapping_lokasi_poli.kd_poli')
                ->select('poliklinik.kd_poli as kode', 'poliklinik.nm_poli as nama', 'satu_sehat_mapping_lokasi_poli.id_lokasi_satusehat as id_fhir');
        } elseif ($type == 'bangsal') {
            $query = DB::connection('simrs')->table('bangsal')
                ->leftJoin('satu_sehat_mapping_lokasi_bangsal', 'bangsal.kd_bangsal', '=', 'satu_sehat_mapping_lokasi_bangsal.kd_bangsal')
                ->select('bangsal.kd_bangsal as kode', 'bangsal.nm_bangsal as nama', 'satu_sehat_mapping_lokasi_bangsal.id_lokasi_satusehat as id_fhir');
        } else {
            // Default to poli if type not recognized
            $query = DB::connection('simrs')->table('poliklinik')
                ->leftJoin('satu_sehat_mapping_lokasi_poli', 'poliklinik.kd_poli', '=', 'satu_sehat_mapping_lokasi_poli.kd_poli')
                ->select('poliklinik.kd_poli as kode', 'poliklinik.nm_poli as nama', 'satu_sehat_mapping_lokasi_poli.id_lokasi_satusehat as id_fhir');
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('nama', 'like', "%$keyword%")->orWhere('kode', 'like', "%$keyword%");
            });
        }

        $mappings = ($perPage == 'all') ? $query->paginate(1000) : $query->paginate($perPage);

        return view('satusehat.mapping_lokasi', compact('mappings', 'keyword', 'perPage', 'type'));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $table = 'satu_sehat_mapping_lokasi_' . $data['type'];
            $key = $data['type'] == 'poli' ? 'kd_poli' : 'kd_bangsal';

            DB::connection('simrs')->table($table)->updateOrInsert(
                [$key => $data['kode']],
                ['id_lokasi_satusehat' => $data['id_fhir']]
            );

            return response()->json(['ok' => true, 'msg' => 'Mapping Lokasi berhasil disimpan']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }
}
