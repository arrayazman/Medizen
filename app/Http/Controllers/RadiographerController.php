<?php

namespace App\Http\Controllers;

use App\Models\Radiographer;
use App\Services\AuditService;
use Illuminate\Http\Request;

class RadiographerController extends Controller
{
    public function index(Request $request)
    {
        $query = Radiographer::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $radiographers = $query->latest()->paginate(15);
        return view('master.radiographers.index', compact('radiographers'));
    }

    public function create()
    {
        return view('master.radiographers.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sip_number' => 'nullable|string|max:50|unique:radiographers',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $radiographer = Radiographer::create($validated);
        AuditService::logCreate($radiographer);

        return redirect()->route('master.radiographers.index')
            ->with('success', 'Radiografer berhasil ditambahkan');
    }

    public function edit(Radiographer $radiographer)
    {
        return view('master.radiographers.form', compact('radiographer'));
    }

    public function update(Request $request, Radiographer $radiographer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sip_number' => 'nullable|string|max:50|unique:radiographers,sip_number,' . $radiographer->id,
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $old = $radiographer->toArray();
        $radiographer->update($validated);
        AuditService::logUpdate($radiographer, $old);

        return redirect()->route('master.radiographers.index')
            ->with('success', 'Data radiografer berhasil diperbarui');
    }

    public function destroy(Radiographer $radiographer)
    {
        AuditService::logDelete($radiographer);
        $radiographer->delete();

        return redirect()->route('master.radiographers.index')
            ->with('success', 'Radiografer berhasil dihapus');
    }
}
