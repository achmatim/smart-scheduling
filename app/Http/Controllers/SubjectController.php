<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('code')->get();
        return view('subjects.index', compact('subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:subjects,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:umum,praktek,olahraga',
        ]);

        Subject::create([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return redirect()->route('subjects.index')->with('success', 'Mata Pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, Subject $subject)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:subjects,code,' . $subject->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:umum,praktek,olahraga',
        ]);

        $subject->update([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return redirect()->route('subjects.index')->with('success', 'Mata Pelajaran berhasil diperbarui.');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('subjects.index')->with('success', 'Mata Pelajaran berhasil dihapus.');
    }
}
