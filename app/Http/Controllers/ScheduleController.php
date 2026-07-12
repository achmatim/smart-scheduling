<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateScheduleJob;
use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchedulingJob;
use App\Models\Teacher;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Display the schedules grid.
     */
    public function index(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            return redirect()->route('dashboard')->with('error', 'Silakan aktifkan Tahun Akademik terlebih dahulu.');
        }

        // Dropdowns for filtering
        $rombels = Rombel::orderBy('name')->get();
        $teachers = Teacher::orderBy('name')->get();
        $rooms = Room::orderBy('code')->get();

        // Selected filter type and ID
        $filterType = $request->get('filter_type', 'rombel'); // rombel, teacher, room
        $filterId = $request->get('filter_id');

        // Set default filter ID if not provided
        if (!$filterId) {
            if ($filterType === 'rombel' && $rombels->isNotEmpty()) {
                $filterId = $rombels->first()->id;
            } elseif ($filterType === 'teacher' && $teachers->isNotEmpty()) {
                $filterId = $teachers->first()->id;
            } elseif ($filterType === 'room' && $rooms->isNotEmpty()) {
                $filterId = $rooms->first()->id;
            }
        }

        // Get schedules for the active year based on filters
        $query = Schedule::where('academic_year_id', $activeYear->id)
            ->with(['rombel', 'subject', 'teacher', 'room']);

        if ($filterType === 'rombel') {
            $query->where('rombel_id', $filterId);
        } elseif ($filterType === 'teacher') {
            $query->where('teacher_id', $filterId);
        } elseif ($filterType === 'room') {
            $query->where('room_id', $filterId);
        }

        $schedules = $query->get();

        // Check if there is a running/pending scheduling job
        $runningJob = SchedulingJob::where('academic_year_id', $activeYear->id)
            ->whereIn('status', ['pending', 'running'])
            ->orderBy('id', 'desc')
            ->first();

        // Check if the current schedule is locked
        $isLocked = Schedule::where('academic_year_id', $activeYear->id)
            ->where('status', 'locked')
            ->exists();

        // Check if academic year itself is locked
        $isYearLocked = $activeYear->is_locked;

        $periods = \App\Models\Period::orderBy('period_number')->get();

        return view('schedules.index', compact(
            'activeYear',
            'rombels',
            'teachers',
            'rooms',
            'filterType',
            'filterId',
            'schedules',
            'runningJob',
            'isLocked',
            'isYearLocked',
            'periods'
        ));
    }

    /**
     * Export schedule to Excel using HTML-to-XLS method.
     */
    public function exportExcel(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            return redirect()->route('dashboard')->with('error', 'Silakan buat atau aktifkan Tahun Akademik terlebih dahulu.');
        }

        $filterType = $request->query('filter_type', 'rombel');
        $filterId = $request->query('filter_id');

        $rombels = Rombel::orderBy('name')->get();
        $teachers = Teacher::orderBy('name')->get();
        $rooms = Room::orderBy('code')->get();

        if (!$filterId) {
            if ($filterType === 'rombel' && $rombels->isNotEmpty()) {
                $filterId = $rombels->first()->id;
            } elseif ($filterType === 'teacher' && $teachers->isNotEmpty()) {
                $filterId = $teachers->first()->id;
            } elseif ($filterType === 'room' && $rooms->isNotEmpty()) {
                $filterId = $rooms->first()->id;
            }
        }

        $query = Schedule::where('academic_year_id', $activeYear->id);
        
        $name = '';
        if ($filterType === 'rombel') {
            $query->where('rombel_id', $filterId);
            $romb = Rombel::find($filterId);
            $name = $romb ? "Kelas " . $romb->name : "";
        } elseif ($filterType === 'teacher') {
            $query->where('teacher_id', $filterId);
            $teach = Teacher::find($filterId);
            $name = $teach ? $teach->name : "";
        } elseif ($filterType === 'room') {
            $query->where('room_id', $filterId);
            $rm = Room::find($filterId);
            $name = $rm ? $rm->code . " - " . $rm->name : "";
        }

        $schedules = $query->with(['subject', 'teacher', 'rombel', 'room'])->get();
        $periods = \App\Models\Period::orderBy('period_number')->get();

        $filename = "jadwal_" . $filterType . "_" . str_replace([' ', '/', '\\', ':'], '_', $name) . ".xls";

        return response()->view('schedules.excel', compact(
            'activeYear',
            'filterType',
            'filterId',
            'name',
            'schedules',
            'periods'
        ))
        ->header('Content-Type', 'application/vnd.ms-excel')
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
        ->header('Cache-Control', 'max-age=0');
    }

    /**
     * Trigger background schedule generation.
     */
    public function generate(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            return response()->json(['error' => 'Tidak ada tahun akademik aktif.'], 400);
        }

        if ($activeYear->is_locked) {
            return response()->json(['error' => 'Jadwal untuk semester ini telah dikunci.'], 400);
        }

        // Check if a job is already running
        $existingJob = SchedulingJob::where('academic_year_id', $activeYear->id)
            ->whereIn('status', ['pending', 'running'])
            ->exists();

        if ($existingJob) {
            return response()->json(['error' => 'Proses generate jadwal sedang berjalan.'], 400);
        }

        // GA parameters from request or defaults
        $popSize = (int) $request->get('pop_size', 80);
        $maxGenerations = (int) $request->get('max_generations', 100);

        // Create database tracking job
        $job = SchedulingJob::create([
            'academic_year_id' => $activeYear->id,
            'status' => 'pending',
            'progress' => 0,
            'max_generations' => $maxGenerations,
            'current_generation' => 0,
            'best_fitness' => 0.0,
            'conflicts' => 0,
        ]);

        // Dispatch background job
        GenerateScheduleJob::dispatch($activeYear->id, $job->id, [
            'pop_size' => $popSize,
            'max_generations' => $maxGenerations
        ]);

        return response()->json([
            'success' => true,
            'job_id' => $job->id,
            'message' => 'Proses pembuatan jadwal otomatis dimulai di background.'
        ]);
    }

    /**
     * API to check background job progress.
     */
    public function checkProgress($jobId)
    {
        $job = SchedulingJob::find($jobId);
        if (!$job) {
            return response()->json(['error' => 'Job tidak ditemukan.'], 404);
        }

        return response()->json([
            'status' => $job->status,
            'progress' => $job->progress,
            'current_generation' => $job->current_generation,
            'max_generations' => $job->max_generations,
            'best_fitness' => round($job->best_fitness, 5),
            'conflicts' => $job->conflicts,
            'error_message' => $job->error_message,
        ]);
    }

    /**
     * Lock draft schedules for active academic year.
     */
    public function lock(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tidak ada tahun akademik aktif.');
        }

        // Lock schedules
        Schedule::where('academic_year_id', $activeYear->id)
            ->update(['status' => 'locked']);

        // Lock academic year too
        $activeYear->update(['is_locked' => true]);

        return redirect()->back()->with('success', 'Jadwal berhasil diverifikasi dan dikunci!');
    }

    /**
     * Unlock schedules for active academic year.
     */
    public function unlock(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tidak ada tahun akademik aktif.');
        }

        // Unlock schedules
        Schedule::where('academic_year_id', $activeYear->id)
            ->update(['status' => 'draft']);

        // Unlock academic year too
        $activeYear->update(['is_locked' => false]);

        return redirect()->back()->with('success', 'Kunci jadwal dibuka. Jadwal sekarang dapat disusun ulang.');
    }
}
