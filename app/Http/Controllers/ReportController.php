<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\Period;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Display report listing with filters and sorting.
     */
    public function index(Request $request)
    {
        // 1. Fetch academic years for filter dropdown
        $academicYears = AcademicYear::orderBy('year', 'desc')->orderBy('semester', 'desc')->get();
        
        // Resolve active year
        $activeYear = AcademicYear::where('is_active', true)->first();
        $selectedYearId = $request->query('academic_year_id', $activeYear ? $activeYear->id : null);
        
        $selectedYear = null;
        if ($selectedYearId) {
            $selectedYear = AcademicYear::find($selectedYearId);
        }

        // Sorting option: teacher, rombel, subject, day
        $sortBy = $request->query('sort_by', 'day');

        // 2. Query schedules
        $schedules = collect();
        if ($selectedYearId) {
            $query = Schedule::where('academic_year_id', $selectedYearId)
                ->with(['teacher', 'rombel', 'subject', 'room']);

            // Apply ordering based on selection
            if ($sortBy === 'teacher') {
                $query->join('teachers', 'schedules.teacher_id', '=', 'teachers.id')
                    ->orderBy('teachers.name')
                    ->orderBy('schedules.day_of_week')
                    ->orderBy('schedules.start_period')
                    ->select('schedules.*');
            } elseif ($sortBy === 'rombel') {
                $query->join('rombels', 'schedules.rombel_id', '=', 'rombels.id')
                    ->orderBy('rombels.name')
                    ->orderBy('schedules.day_of_week')
                    ->orderBy('schedules.start_period')
                    ->select('schedules.*');
            } elseif ($sortBy === 'subject') {
                $query->join('subjects', 'schedules.subject_id', '=', 'subjects.id')
                    ->orderBy('subjects.name')
                    ->orderBy('schedules.day_of_week')
                    ->orderBy('schedules.start_period')
                    ->select('schedules.*');
            } else { // default 'day'
                $query->orderBy('schedules.day_of_week')
                    ->orderBy('schedules.start_period');
            }

            $schedules = $query->get();
        }

        // 3. Fetch periods for time formatting
        $periods = Period::orderBy('period_number')->get();
        $periodsMap = [];
        foreach ($periods as $p) {
            $periodsMap[$p->period_number] = $p;
        }

        $daysMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat'
        ];

        return view('reports.index', compact(
            'academicYears',
            'selectedYearId',
            'selectedYear',
            'sortBy',
            'schedules',
            'periodsMap',
            'daysMap'
        ));
    }

    /**
     * Display printable print view.
     */
    public function print(Request $request)
    {
        $selectedYearId = $request->query('academic_year_id');
        $selectedYear = AcademicYear::findOrFail($selectedYearId);
        $sortBy = $request->query('sort_by', 'day');

        $query = Schedule::where('academic_year_id', $selectedYearId)
            ->with(['teacher', 'rombel', 'subject', 'room']);

        if ($sortBy === 'teacher') {
            $query->join('teachers', 'schedules.teacher_id', '=', 'teachers.id')
                ->orderBy('teachers.name')
                ->orderBy('schedules.day_of_week')
                ->orderBy('schedules.start_period')
                ->select('schedules.*');
        } elseif ($sortBy === 'rombel') {
            $query->join('rombels', 'schedules.rombel_id', '=', 'rombels.id')
                ->orderBy('rombels.name')
                ->orderBy('schedules.day_of_week')
                ->orderBy('schedules.start_period')
                ->select('schedules.*');
        } elseif ($sortBy === 'subject') {
            $query->join('subjects', 'schedules.subject_id', '=', 'subjects.id')
                ->orderBy('subjects.name')
                ->orderBy('schedules.day_of_week')
                ->orderBy('schedules.start_period')
                ->select('schedules.*');
        } else {
            $query->orderBy('schedules.day_of_week')
                ->orderBy('schedules.start_period');
        }

        $schedules = $query->get();
        $periods = Period::orderBy('period_number')->get();
        
        $periodsMap = [];
        foreach ($periods as $p) {
            $periodsMap[$p->period_number] = $p;
        }

        $daysMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat'
        ];

        return view('reports.print', compact('selectedYear', 'sortBy', 'schedules', 'periodsMap', 'daysMap'));
    }

    /**
     * Export report data as Excel.
     */
    public function excel(Request $request)
    {
        $selectedYearId = $request->query('academic_year_id');
        $selectedYear = AcademicYear::findOrFail($selectedYearId);
        $sortBy = $request->query('sort_by', 'day');

        $query = Schedule::where('academic_year_id', $selectedYearId)
            ->with(['teacher', 'rombel', 'subject', 'room']);

        if ($sortBy === 'teacher') {
            $query->join('teachers', 'schedules.teacher_id', '=', 'teachers.id')
                ->orderBy('teachers.name')
                ->orderBy('schedules.day_of_week')
                ->orderBy('schedules.start_period')
                ->select('schedules.*');
        } elseif ($sortBy === 'rombel') {
            $query->join('rombels', 'schedules.rombel_id', '=', 'rombels.id')
                ->orderBy('rombels.name')
                ->orderBy('schedules.day_of_week')
                ->orderBy('schedules.start_period')
                ->select('schedules.*');
        } elseif ($sortBy === 'subject') {
            $query->join('subjects', 'schedules.subject_id', '=', 'subjects.id')
                ->orderBy('subjects.name')
                ->orderBy('schedules.day_of_week')
                ->orderBy('schedules.start_period')
                ->select('schedules.*');
        } else {
            $query->orderBy('schedules.day_of_week')
                ->orderBy('schedules.start_period');
        }

        $schedules = $query->get();
        $periods = Period::orderBy('period_number')->get();
        
        $periodsMap = [];
        foreach ($periods as $p) {
            $periodsMap[$p->period_number] = $p;
        }

        $daysMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat'
        ];

        $filename = "laporan_jadwal_" . str_replace([' ', '/', '\\'], '_', $selectedYear->year) . "_" . $selectedYear->semester . ".xls";

        return response()->view('reports.excel', compact('selectedYear', 'sortBy', 'schedules', 'periodsMap', 'daysMap'))
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }
}
