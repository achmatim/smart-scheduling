<?php

namespace App\Http\Controllers;

use App\Models\Rombel;
use Illuminate\Http\Request;

class RombelController extends Controller
{
    public function index()
    {
        $rombels = Rombel::orderBy('name')->get();
        return view('rombels.index', compact('rombels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:rombels,name',
            'grade' => 'required|integer|in:7,8,9',
        ]);

        Rombel::create([
            'name' => strtoupper($request->name),
            'grade' => $request->grade,
        ]);

        return redirect()->route('rombels.index')->with('success', 'Rombongan Belajar berhasil ditambahkan.');
    }

    public function update(Request $request, Rombel $rombel)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:rombels,name,' . $rombel->id,
            'grade' => 'required|integer|in:7,8,9',
        ]);

        $rombel->update([
            'name' => strtoupper($request->name),
            'grade' => $request->grade,
        ]);

        return redirect()->route('rombels.index')->with('success', 'Rombongan Belajar berhasil diperbarui.');
    }

    public function destroy(Rombel $rombel)
    {
        $rombel->delete();
        return redirect()->route('rombels.index')->with('success', 'Rombongan Belajar berhasil dihapus.');
    }
}
