<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MappingVaksinController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $perPage = $request->get('per_page', 25);

        $query = DB::connection('simrs')->table('databarang')
            ->leftJoin('satu_sehat_mapping_vaksin', 'databarang.kode_brng', '=', 'satu_sehat_mapping_vaksin.kode_brng')
            ->select(
                'databarang.kode_brng',
                'databarang.nama_brng',
                'satu_sehat_mapping_vaksin.vaksin_code',
                'satu_sehat_mapping_vaksin.vaksin_system',
                'satu_sehat_mapping_vaksin.vaksin_display',
                'satu_sehat_mapping_vaksin.route_code',
                'satu_sehat_mapping_vaksin.route_system',
                'satu_sehat_mapping_vaksin.route_display',
                'satu_sehat_mapping_vaksin.dose_quantity_code',
                'satu_sehat_mapping_vaksin.dose_quantity_system',
                'satu_sehat_mapping_vaksin.dose_quantity_unit'
            );

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('databarang.nama_brng', 'like', "%$keyword%")
                  ->orWhere('databarang.kode_brng', 'like', "%$keyword%")
                  ->orWhere('satu_sehat_mapping_vaksin.vaksin_code', 'like', "%$keyword%")
                  ->orWhere('satu_sehat_mapping_vaksin.vaksin_display', 'like', "%$keyword%");
            });
        }

        $mappings = ($perPage == 'all') ? $query->paginate(1000) : $query->paginate($perPage);

        return view('satusehat.mapping_vaksin', compact('mappings', 'keyword', 'perPage'));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            
            DB::connection('simrs')->table('satu_sehat_mapping_vaksin')->updateOrInsert(
                ['kode_brng' => $data['kode_brng']],
                [
                    'vaksin_code' => $data['vaksin_code'],
                    'vaksin_system' => $data['vaksin_system'] ?: 'http://sys-ids.kemkes.go.id/kfa',
                    'vaksin_display' => $data['vaksin_display'],
                    'route_code' => $data['route_code'],
                    'route_system' => $data['route_system'] ?: 'http://www.whocc.no/atc',
                    'route_display' => $data['route_display'],
                    'dose_quantity_code' => $data['dose_quantity_code'],
                    'dose_quantity_system' => $data['dose_quantity_system'] ?: 'http://unitsofmeasure.org',
                    'dose_quantity_unit' => $data['dose_quantity_unit'],
                ]
            );

            return response()->json(['ok' => true, 'msg' => 'Mapping Vaksin berhasil disimpan']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('satu_sehat_mapping_vaksin')->where('kode_brng', $id)->delete();
            return response()->json(['ok' => true, 'msg' => 'Mapping Vaksin berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }
}
