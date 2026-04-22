<?php

namespace App\Http\Controllers;

use App\Models\ExaminationType;
use App\Models\Modality;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ExaminationTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ExaminationType::with('modality');

        if ($request->filled('modality_id')) {
            $query->where('modality_id', $request->modality_id);
        }

        $examinationTypes = $query->latest()->paginate(15);
        $modalities = Modality::active()->get();

        return view('master.examination-types.index', compact('examinationTypes', 'modalities'));
    }

    public function create()
    {
        $modalities = Modality::active()->get();
        return view('master.examination-types.form', compact('modalities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'modality_id' => 'required|exists:modalities,id',
            'code' => 'required|string|max:20|unique:examination_types',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'js_rs' => 'nullable|numeric|min:0',
            'paket_bhp' => 'nullable|numeric|min:0',
            'jm_dokter' => 'nullable|numeric|min:0',
            'jm_petugas' => 'nullable|numeric|min:0',
            'jm_perujuk' => 'nullable|numeric|min:0',
            'kso' => 'nullable|numeric|min:0',
            'manajemen' => 'nullable|numeric|min:0',
            'jenis_bayar_kd' => 'nullable|string|max:20',
            'jenis_bayar_nama' => 'nullable|string|max:100',
            'kelas' => 'nullable|string|max:50',
        ]);

        $exam = ExaminationType::create($validated);
        AuditService::logCreate($exam);

        return redirect()->route('master.examination-types.index')
            ->with('success', 'Jenis pemeriksaan berhasil ditambahkan');
    }

    public function edit(ExaminationType $examinationType)
    {
        $modalities = Modality::active()->get();
        return view('master.examination-types.form', compact('examinationType', 'modalities'));
    }

    public function update(Request $request, ExaminationType $examinationType)
    {
        $validated = $request->validate([
            'modality_id' => 'required|exists:modalities,id',
            'code' => 'required|string|max:20|unique:examination_types,code,' . $examinationType->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'js_rs' => 'nullable|numeric|min:0',
            'paket_bhp' => 'nullable|numeric|min:0',
            'jm_dokter' => 'nullable|numeric|min:0',
            'jm_petugas' => 'nullable|numeric|min:0',
            'jm_perujuk' => 'nullable|numeric|min:0',
            'kso' => 'nullable|numeric|min:0',
            'manajemen' => 'nullable|numeric|min:0',
            'jenis_bayar_kd' => 'nullable|string|max:20',
            'jenis_bayar_nama' => 'nullable|string|max:100',
            'kelas' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $old = $examinationType->toArray();
        $examinationType->update($validated);
        AuditService::logUpdate($examinationType, $old);

        return redirect()->route('master.examination-types.index')
            ->with('success', 'Jenis pemeriksaan berhasil diperbarui');
    }

    public function destroy(ExaminationType $examinationType)
    {
        AuditService::logDelete($examinationType);
        $examinationType->delete();

        return redirect()->route('master.examination-types.index')
            ->with('success', 'Jenis pemeriksaan berhasil dihapus');
    }
}
