<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SimrsModalityMap;
use Illuminate\Support\Facades\DB;

class SimrsModalityMapController extends Controller
{
    // Daftar kode modality standar DICOM
    const MODALITY_CODES = [
        'CR' => 'CR - Computed Radiography',
        'CT' => 'CT - Computed Tomography',
        'MR' => 'MR - Magnetic Resonance',
        'US' => 'US - Ultrasound',
        'DX' => 'DX - Digital Radiography',
        'MG' => 'MG - Mammography',
        'NM' => 'NM - Nuclear Medicine',
        'PT' => 'PT - PET Scan',
        'OT' => 'OT - Other',
        'XA' => 'XA - X-Ray Angiography',
        'RF' => 'RF - Radio Fluoroscopy',
    ];

    public function index(Request $request)
    {
        $search = $request->query('search');

        $maps = SimrsModalityMap::when($search, function ($q) use ($search) {
                $q->where('kd_jenis_prw', 'like', "%{$search}%")
                    ->orWhere('nm_perawatan', 'like', "%{$search}%")
                    ->orWhere('modality_code', 'like', "%{$search}%");
            })
            ->orderBy('nm_perawatan')
            ->paginate(30);

        $modalityCodes = self::MODALITY_CODES;

        return view('simrs.modality-map.index', compact('maps', 'search', 'modalityCodes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kd_jenis_prw' => 'required|string|unique:simrs_modality_maps,kd_jenis_prw',
            'nm_perawatan' => 'nullable|string|max:255',
            'modality_code' => 'required|string|max:10',
            'notes' => 'nullable|string',
        ]);

        SimrsModalityMap::create($request->only([
            'kd_jenis_prw',
            'nm_perawatan',
            'modality_code',
            'notes'
        ]));

        return back()->with('success', "Mapping kode '{$request->kd_jenis_prw}' berhasil ditambahkan.");
    }

    public function update(Request $request, SimrsModalityMap $map)
    {
        $request->validate([
            'nm_perawatan' => 'nullable|string|max:255',
            'modality_code' => 'required|string|max:10',
            'notes' => 'nullable|string',
        ]);

        $map->update($request->only([
            'nm_perawatan',
            'modality_code',
            'notes'
        ]));

        if ($request->has('update_same_name') && $map->nm_perawatan) {
            SimrsModalityMap::where('nm_perawatan', $map->nm_perawatan)
                ->where('id', '!=', $map->id)
                ->update(['modality_code' => $request->modality_code]);
        }

        return back()->with('success', "Mapping '{$map->kd_jenis_prw}' berhasil diperbarui.");
    }

    public function destroy(SimrsModalityMap $map)
    {
        $code = $map->kd_jenis_prw;
        $map->delete();
        return back()->with('success', "Mapping '{$code}' berhasil dihapus.");
    }

    /**
     * Import otomatis dari SIMRS: ambil semua kd_jenis_prw yang belum ada mapping
     */
    public function importFromSimrs()
    {
        try {
            $existing = SimrsModalityMap::pluck('kd_jenis_prw')->toArray();

            $rows = DB::connection('simrs')
                ->table('jns_perawatan_radiologi')
                ->select('kd_jenis_prw', 'nm_perawatan')
                ->whereNotIn('kd_jenis_prw', $existing)
                ->get();

            $count = 0;
            foreach ($rows as $row) {
                SimrsModalityMap::create([
                    'kd_jenis_prw' => $row->kd_jenis_prw,
                    'nm_perawatan' => $row->nm_perawatan,
                    'modality_code' => 'CR', // default
                ]);
                $count++;
            }

            return back()->with('success', "Berhasil import {$count} item dari SIMRS. Silakan update modality code masing-masing.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal import dari SIMRS: ' . $e->getMessage());
        }
    }

    /**
     * API: lookup modality code by kd_jenis_prw (used by SimrsController)
     */
    public static function resolveModality(string $kdJenisPrw): ?string
    {
        $map = SimrsModalityMap::where('kd_jenis_prw', $kdJenisPrw)->first();
        return $map?->modality_code;
    }
}
