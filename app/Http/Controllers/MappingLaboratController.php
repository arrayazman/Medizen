<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MappingLaboratController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $perPage = $request->get('per_page', 25);

        $query = DB::connection('simrs')->table('template_laborat')
            ->leftJoin('satu_sehat_mapping_lab', 'template_laborat.id_template', '=', 'satu_sehat_mapping_lab.id_template')
            ->select(
                'template_laborat.kd_jenis_prw',
                'template_laborat.id_template',
                'template_laborat.Pemeriksaan',
                'satu_sehat_mapping_lab.code',
                'satu_sehat_mapping_lab.system',
                'satu_sehat_mapping_lab.display',
                'satu_sehat_mapping_lab.sampel_code',
                'satu_sehat_mapping_lab.sampel_system',
                'satu_sehat_mapping_lab.sampel_display'
            );

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('template_laborat.Pemeriksaan', 'like', "%$keyword%")
                  ->orWhere('template_laborat.id_template', 'like', "%$keyword%")
                  ->orWhere('satu_sehat_mapping_lab.code', 'like', "%$keyword%")
                  ->orWhere('satu_sehat_mapping_lab.display', 'like', "%$keyword%");
            });
        }

        $mappings = ($perPage == 'all') ? $query->paginate(1000) : $query->paginate($perPage);

        return view('satusehat.mapping_laborat', compact('mappings', 'keyword', 'perPage'));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'id_template' => 'required',
                'code' => 'required',
                'system' => 'required',
                'display' => 'required',
                'sampel_code' => 'required',
                'sampel_system' => 'required',
                'sampel_display' => 'required',
            ]);

            DB::connection('simrs')->table('satu_sehat_mapping_lab')->updateOrInsert(
                ['id_template' => $data['id_template']],
                [
                    'code' => $data['code'],
                    'system' => $data['system'],
                    'display' => $data['display'],
                    'sampel_code' => $data['sampel_code'],
                    'sampel_system' => $data['sampel_system'],
                    'sampel_display' => $data['sampel_display'],
                ]
            );

            return response()->json(['ok' => true, 'msg' => 'Mapping Laborat berhasil disimpan']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('satu_sehat_mapping_lab')->where('id_template', $id)->delete();
            return response()->json(['ok' => true, 'msg' => 'Mapping Laborat berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }
}
