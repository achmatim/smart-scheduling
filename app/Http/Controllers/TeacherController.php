<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\TeacherAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::orderBy('name')->get();
        return view('teachers.index', compact('teachers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nip' => 'nullable|string|unique:teachers,nip|max:30',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        DB::transaction(function () use ($request) {
            $teacher = Teacher::create([
                'nip' => $request->nip,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ]);

            // Initialize availability to all true (5 days, 8 periods)
            $availabilities = [];
            for ($day = 1; $day <= 5; $day++) {
                for ($period = 1; $period <= 8; $period++) {
                    $availabilities[] = [
                        'teacher_id' => $teacher->id,
                        'day_of_week' => $day,
                        'period_number' => $period,
                        'is_available' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            TeacherAvailability::insert($availabilities);
        });

        return redirect()->route('teachers.index')->with('success', 'Guru berhasil ditambahkan.');
    }

    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'nip' => 'nullable|string|max:30|unique:teachers,nip,' . $teacher->id,
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $teacher->update([
            'nip' => $request->nip,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return redirect()->route('teachers.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();
        return redirect()->route('teachers.index')->with('success', 'Guru berhasil dihapus.');
    }
}
