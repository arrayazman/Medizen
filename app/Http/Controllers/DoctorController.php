<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Services\AuditService;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $query = Doctor::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sip_number', 'like', '%' . $request->search . '%');
        }

        $doctors = $query->latest()->paginate(15);

        return view('master.doctors.index', compact('doctors'));
    }

    public function create()
    {
        return view('master.doctors.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'sip_number' => 'nullable|string|max:50|unique:doctors',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $doctor = Doctor::create($validated);
        AuditService::logCreate($doctor, 'Dokter baru ditambahkan');

        return redirect()->route('master.doctors.index')
            ->with('success', 'Dokter berhasil ditambahkan');
    }

    public function edit(Doctor $doctor)
    {
        return view('master.doctors.form', compact('doctor'));
    }

    public function update(Request $request, Doctor $doctor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'sip_number' => 'nullable|string|max:50|unique:doctors,sip_number,' . $doctor->id,
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $old = $doctor->toArray();
        $doctor->update($validated);
        AuditService::logUpdate($doctor, $old, 'Data dokter diperbarui');

        return redirect()->route('master.doctors.index')
            ->with('success', 'Data dokter berhasil diperbarui');
    }

    public function destroy(Doctor $doctor)
    {
        AuditService::logDelete($doctor, 'Dokter dihapus');
        $doctor->delete();

        return redirect()->route('master.doctors.index')
            ->with('success', 'Dokter berhasil dihapus');
    }
}
