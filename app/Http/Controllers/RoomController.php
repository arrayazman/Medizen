<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Modality;
use App\Services\AuditService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with('modality')->latest()->paginate(15);
        return view('master.rooms.index', compact('rooms'));
    }

    public function create()
    {
        $modalities = Modality::active()->get();
        return view('master.rooms.form', compact('modalities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:rooms',
            'modality_id' => 'nullable|exists:modalities,id',
            'floor' => 'nullable|string|max:50',
        ]);

        $room = Room::create($validated);
        AuditService::logCreate($room);

        return redirect()->route('master.rooms.index')
            ->with('success', 'Ruangan berhasil ditambahkan');
    }

    public function edit(Room $room)
    {
        $modalities = Modality::active()->get();
        return view('master.rooms.form', compact('room', 'modalities'));
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:rooms,code,' . $room->id,
            'modality_id' => 'nullable|exists:modalities,id',
            'floor' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $old = $room->toArray();
        $room->update($validated);
        AuditService::logUpdate($room, $old);

        return redirect()->route('master.rooms.index')
            ->with('success', 'Ruangan berhasil diperbarui');
    }

    public function destroy(Room $room)
    {
        AuditService::logDelete($room);
        $room->delete();

        return redirect()->route('master.rooms.index')
            ->with('success', 'Ruangan berhasil dihapus');
    }
}
