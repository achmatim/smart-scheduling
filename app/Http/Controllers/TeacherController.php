<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\TenantManager;

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
            'nip' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('teachers', 'nip')->where('school_id', TenantManager::getSchoolId())
            ],
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

            // Initialize availability to all true based on defined periods
            $periodNumbers = Period::pluck('period_number')->toArray();
            if (empty($periodNumbers)) {
                $periodNumbers = range(1, 10);
            }

            $availabilities = [];
            for ($day = 1; $day <= 5; $day++) {
                foreach ($periodNumbers as $period) {
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
            'nip' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('teachers', 'nip')->ignore($teacher->id)->where('school_id', TenantManager::getSchoolId())
            ],
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
