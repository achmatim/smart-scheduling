<?php

namespace App\Http\Controllers;

use App\Models\Rombel;
use App\Models\Room;
use Illuminate\Http\Request;

class RombelController extends Controller
{
    public function index()
    {
        $rombels = Rombel::with('room')->orderBy('name')->get();
        $rooms = Room::orderBy('code')->get();
        return view('rombels.index', compact('rombels', 'rooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:rombels,name',
            'grade' => 'required|integer|in:7,8,9',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        Rombel::create([
            'name' => strtoupper($request->name),
            'grade' => $request->grade,
            'room_id' => $request->room_id,
        ]);

        return redirect()->route('rombels.index')->with('success', 'Rombongan Belajar berhasil ditambahkan.');
    }

    public function update(Request $request, Rombel $rombel)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:rombels,name,' . $rombel->id,
            'grade' => 'required|integer|in:7,8,9',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        $rombel->update([
            'name' => strtoupper($request->name),
            'grade' => $request->grade,
            'room_id' => $request->room_id,
        ]);

        return redirect()->route('rombels.index')->with('success', 'Rombongan Belajar berhasil diperbarui.');
    }

    public function destroy(Rombel $rombel)
    {
        $rombel->delete();
        return redirect()->route('rombels.index')->with('success', 'Rombongan Belajar berhasil dihapus.');
    }
}
