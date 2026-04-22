<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MappingRadiologiController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $perPage = $request->get('per_page', 25);

        $query = DB::connection('simrs')->table('jns_perawatan_radiologi')
            ->leftJoin('satu_sehat_mapping_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw', '=', 'satu_sehat_mapping_radiologi.kd_jenis_prw')
            ->select(
                'jns_perawatan_radiologi.kd_jenis_prw',
                'jns_perawatan_radiologi.nm_perawatan',
                'satu_sehat_mapping_radiologi.code',
                'satu_sehat_mapping_radiologi.system',
                'satu_sehat_mapping_radiologi.display',
                'satu_sehat_mapping_radiologi.sampel_code',
                'satu_sehat_mapping_radiologi.sampel_system',
                'satu_sehat_mapping_radiologi.sampel_display'
            );

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('jns_perawatan_radiologi.nm_perawatan', 'like', "%$keyword%")
                  ->orWhere('jns_perawatan_radiologi.kd_jenis_prw', 'like', "%$keyword%")
                  ->orWhere('satu_sehat_mapping_radiologi.code', 'like', "%$keyword%")
                  ->orWhere('satu_sehat_mapping_radiologi.display', 'like', "%$keyword%");
            });
        }

        $mappings = ($perPage == 'all') ? $query->paginate(1000) : $query->paginate($perPage);

        return view('satusehat.mapping_radiologi', compact('mappings', 'keyword', 'perPage'));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            
            DB::connection('simrs')->table('satu_sehat_mapping_radiologi')->updateOrInsert(
                ['kd_jenis_prw' => $data['kd_jenis_prw']],
                [
                    'code' => $data['code'],
                    'system' => $data['system'] ?: 'http://loinc.org',
                    'display' => $data['display'],
                    'sampel_code' => $data['sampel_code'],
                    'sampel_system' => $data['sampel_system'] ?: 'http://snomed.info/sct',
                    'sampel_display' => $data['sampel_display'],
                ]
            );

            return response()->json(['ok' => true, 'msg' => 'Mapping Radiologi berhasil disimpan']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('satu_sehat_mapping_radiologi')->where('kd_jenis_prw', $id)->delete();
            return response()->json(['ok' => true, 'msg' => 'Mapping Radiologi berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }
}
