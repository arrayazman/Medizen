<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MappingObatController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $perPage = $request->get('per_page', 25);

        $query = DB::connection('simrs')->table('databarang')
            ->leftJoin('satu_sehat_mapping_obat', 'databarang.kode_brng', '=', 'satu_sehat_mapping_obat.kode_brng')
            ->select(
                'databarang.kode_brng',
                'databarang.nama_brng',
                'satu_sehat_mapping_obat.obat_code',
                'satu_sehat_mapping_obat.obat_system',
                'satu_sehat_mapping_obat.obat_display',
                'satu_sehat_mapping_obat.form_code',
                'satu_sehat_mapping_obat.form_system',
                'satu_sehat_mapping_obat.form_display',
                'satu_sehat_mapping_obat.numerator_code',
                'satu_sehat_mapping_obat.numerator_system',
                'satu_sehat_mapping_obat.denominator_code',
                'satu_sehat_mapping_obat.denominator_system',
                'satu_sehat_mapping_obat.route_code',
                'satu_sehat_mapping_obat.route_system',
                'satu_sehat_mapping_obat.route_display'
            );

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('databarang.nama_brng', 'like', "%$keyword%")
                  ->orWhere('databarang.kode_brng', 'like', "%$keyword%")
                  ->orWhere('satu_sehat_mapping_obat.obat_code', 'like', "%$keyword%")
                  ->orWhere('satu_sehat_mapping_obat.obat_display', 'like', "%$keyword%");
            });
        }

        $mappings = ($perPage == 'all') ? $query->paginate(1000) : $query->paginate($perPage);

        return view('satusehat.mapping_obat', compact('mappings', 'keyword', 'perPage'));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            
            DB::connection('simrs')->table('satu_sehat_mapping_obat')->updateOrInsert(
                ['kode_brng' => $data['kode_brng']],
                [
                    'obat_code' => $data['obat_code'],
                    'obat_system' => $data['obat_system'] ?: 'http://sys-ids.kemkes.go.id/kfa',
                    'obat_display' => $data['obat_display'],
                    'form_code' => $data['form_code'],
                    'form_system' => $data['form_system'] ?: 'http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm',
                    'form_display' => $data['form_display'],
                    'numerator_code' => $data['numerator_code'],
                    'numerator_system' => $data['numerator_system'] ?: 'http://unitsofmeasure.org',
                    'denominator_code' => $data['denominator_code'],
                    'denominator_system' => $data['denominator_system'] ?: 'http://unitsofmeasure.org',
                    'route_code' => $data['route_code'],
                    'route_system' => $data['route_system'] ?: 'http://www.whocc.no/atc',
                    'route_display' => $data['route_display'],
                ]
            );

            return response()->json(['ok' => true, 'msg' => 'Mapping Obat berhasil disimpan']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('satu_sehat_mapping_obat')->where('kode_brng', $id)->delete();
            return response()->json(['ok' => true, 'msg' => 'Mapping Obat berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }
}
