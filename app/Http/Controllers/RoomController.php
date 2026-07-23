<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\TenantManager;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::orderBy('code')->get();
        return view('rooms.index', compact('rooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('rooms', 'code')->where('school_id', TenantManager::getSchoolId())
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:umum,lab,lapangan',
            'capacity' => 'nullable|integer|min:1',
        ]);

        Room::create([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'type' => $request->type,
            'capacity' => $request->capacity,
        ]);

        return redirect()->route('rooms.index')->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function update(Request $request, Room $room)
    {
        $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('rooms', 'code')->ignore($room->id)->where('school_id', TenantManager::getSchoolId())
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:umum,lab,lapangan',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $room->update([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'type' => $request->type,
            'capacity' => $request->capacity,
        ]);

        return redirect()->route('rooms.index')->with('success', 'Ruangan berhasil diperbarui.');
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return redirect()->route('rooms.index')->with('success', 'Ruangan berhasil dihapus.');
    }
}
