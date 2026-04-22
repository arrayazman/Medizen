<?php

namespace App\Http\Controllers;

use App\Models\Modality;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ModalityController extends Controller
{
    public function index()
    {
        $modalities = Modality::latest()->paginate(15);
        return view('master.modalities.index', compact('modalities'));
    }

    public function create()
    {
        return view('master.modalities.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:modalities',
            'name' => 'required|string|max:255',
            'ae_title' => 'nullable|string|max:64',
            'description' => 'nullable|string',
        ]);

        $modality = Modality::create($validated);
        AuditService::logCreate($modality);

        return redirect()->route('master.modalities.index')
            ->with('success', 'Modalitas berhasil ditambahkan');
    }

    public function edit(Modality $modality)
    {
        return view('master.modalities.form', compact('modality'));
    }

    public function update(Request $request, Modality $modality)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:modalities,code,' . $modality->id,
            'name' => 'required|string|max:255',
            'ae_title' => 'nullable|string|max:64',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $old = $modality->toArray();
        $modality->update($validated);
        AuditService::logUpdate($modality, $old);

        return redirect()->route('master.modalities.index')
            ->with('success', 'Modalitas berhasil diperbarui');
    }

    public function destroy(Modality $modality)
    {
        AuditService::logDelete($modality);
        $modality->delete();

        return redirect()->route('master.modalities.index')
            ->with('success', 'Modalitas berhasil dihapus');
    }
}
