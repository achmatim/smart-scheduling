<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Lesson;
use App\Models\Rombel;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Teacher;
use App\Models\Subject;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display dashboard landing page.
     */
    public function index()
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $academicYears = AcademicYear::orderBy('year', 'desc')->orderBy('semester', 'desc')->get();

        $stats = [
            'teachers' => Teacher::count(),
            'subjects' => Subject::count(),
            'rooms' => Room::count(),
            'rombels' => Rombel::count(),
            'lessons' => $activeYear ? (int) Lesson::where('academic_year_id', $activeYear->id)->sum('total_hours') : 0,
            'schedules' => $activeYear ? (int) Schedule::where('academic_year_id', $activeYear->id)
                ->selectRaw('SUM(end_period - start_period + 1) as total_jp')
                ->value('total_jp') : 0,
            'is_locked' => $activeYear ? (bool) $activeYear->is_locked : false,
        ];

        return view('dashboard', compact('activeYear', 'academicYears', 'stats'));
    }

    /**
     * Store new academic year.
     */
    public function storeYear(Request $request)
    {
        $request->validate([
            'year' => 'required|string|max:9|regex:/^\d{4}\/\d{4}$/', // e.g. 2026/2027
            'semester' => 'required|in:Ganjil,Genap',
        ]);

        // Check duplicate
        $exists = AcademicYear::where('year', $request->year)
            ->where('semester', $request->semester)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Tahun akademik dan semester tersebut sudah terdaftar.');
        }

        AcademicYear::create([
            'year' => $request->year,
            'semester' => $request->semester,
            'is_active' => false,
            'is_locked' => false,
        ]);

        return redirect()->back()->with('success', 'Tahun Akademik berhasil ditambahkan.');
    }

    /**
     * Activate an academic year.
     */
    public function activateYear($id)
    {
        // Deactivate all
        AcademicYear::where('is_active', true)->update(['is_active' => false]);

        // Activate this one
        $ay = AcademicYear::findOrFail($id);
        $ay->update(['is_active' => true]);

        return redirect()->back()->with('success', "Tahun Akademik {$ay->year} - Semester {$ay->semester} berhasil diaktifkan.");
    }
}
