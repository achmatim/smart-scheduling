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
use Illuminate\Support\Facades\Auth;

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

        // Get all schedules to detect clashes across the entire school
        $allSchedules = Schedule::where('academic_year_id', $activeYear->id)
            ->with(['rombel', 'teacher', 'room', 'subject'])
            ->get();

        $clashes = [];
        if ($allSchedules->isNotEmpty()) {
            $teacherUsage = [];
            $rombelUsage = [];
            $roomUsage = [];
            $rombelSubjectDayUsage = [];
            $dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];

            foreach ($allSchedules as $s) {
                $dayName = $dayNames[$s->day_of_week] ?? "Hari {$s->day_of_week}";

                // Same-day subject clash detection
                $subjKey = "{$s->rombel_id}-{$s->subject_id}-{$s->day_of_week}";
                if (isset($rombelSubjectDayUsage[$subjKey])) {
                    $other = $rombelSubjectDayUsage[$subjKey];
                    $clashes[] = "Kelas <strong>{$s->rombel->name}</strong> memiliki mata pelajaran <strong>{$s->subject->name}</strong> pada dua slot berbeda di hari {$dayName} (Jam ke-{$s->start_period} dan Jam ke-{$other->start_period}).";
                } else {
                    $rombelSubjectDayUsage[$subjKey] = $s;
                }

                $duration = $s->end_period - $s->start_period + 1;
                for ($offset = 0; $offset < $duration; $offset++) {
                    $period = $s->start_period + $offset;
                    $key = "{$s->day_of_week}-{$period}";

                    // Teacher clash
                    if (isset($teacherUsage[$s->teacher_id][$key])) {
                        $other = $teacherUsage[$s->teacher_id][$key];
                        $clashes[] = "Guru <strong>{$s->teacher->name}</strong> bentrok mengajar <strong>{$s->subject->name}</strong> ({$s->rombel->name}) dan <strong>{$other->subject->name}</strong> ({$other->rombel->name}) pada hari {$dayName} jam ke-{$period}.";
                    } else {
                        $teacherUsage[$s->teacher_id][$key] = $s;
                    }

                    // Rombel clash
                    if (isset($rombelUsage[$s->rombel_id][$key])) {
                        $other = $rombelUsage[$s->rombel_id][$key];
                        $clashes[] = "Kelas <strong>{$s->rombel->name}</strong> bentrok menerima pelajaran <strong>{$s->subject->name}</strong> (oleh {$s->teacher->name}) dan <strong>{$other->subject->name}</strong> (oleh {$other->teacher->name}) pada hari {$dayName} jam ke-{$period}.";
                    } else {
                        $rombelUsage[$s->rombel_id][$key] = $s;
                    }

                    // Room clash
                    if (isset($roomUsage[$s->room_id][$key])) {
                        $other = $roomUsage[$s->room_id][$key];
                        $clashes[] = "Ruangan <strong>{$s->room->code}</strong> bentrok ditempati Kelas <strong>{$s->rombel->name}</strong> (pelajaran {$s->subject->name}) dan Kelas <strong>{$other->rombel->name}</strong> (pelajaran {$other->subject->name}) pada hari {$dayName} jam ke-{$period}.";
                    } else {
                        $roomUsage[$s->room_id][$key] = $s;
                    }
                }
            }
        }

        $periods = \App\Models\Period::orderBy('period_number')->get();

        // Calculate weekly capacity and bottlenecks based on teacher availability
        $rombelsCount = $rombels->count();
        $weeklyCapacity = 0;
        $bottlenecks = [];
        $dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];

        if ($rombelsCount > 0 && $periods->isNotEmpty()) {
            $teacherAvail = [];
            $availRecords = \App\Models\TeacherAvailability::all();
            foreach ($availRecords as $ar) {
                $teacherAvail[$ar->teacher_id][$ar->day_of_week][$ar->period_number] = $ar->is_available;
            }

            foreach ($dayNames as $dayNum => $dayName) {
                foreach ($periods as $pModel) {
                    if ($pModel->is_break) continue;
                    
                    $availableCount = 0;
                    $unavailableTeachers = [];
                    foreach ($teachers as $t) {
                        $isAvailable = $teacherAvail[$t->id][$dayNum][$pModel->period_number] ?? true;
                        if ($isAvailable) {
                            $availableCount++;
                        } else {
                            $unavailableTeachers[] = $t->name;
                        }
                    }

                    $effectiveSlots = min($rombelsCount, $availableCount);
                    $weeklyCapacity += $effectiveSlots;

                    if ($availableCount < $rombelsCount) {
                        $bottlenecks[] = [
                            'day' => $dayName,
                            'period' => $pModel->period_number,
                            'available' => $availableCount,
                            'needed' => $rombelsCount,
                            'unavailable_teachers' => $unavailableTeachers,
                        ];
                    }
                }
            }
        }

        $totalAllocatedJp = \App\Models\Lesson::where('academic_year_id', $activeYear->id)->sum('total_hours');

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
            'periods',
            'clashes',
            'weeklyCapacity',
            'totalAllocatedJp',
            'bottlenecks'
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

        // Get all schedules for the active academic year
        $schedules = Schedule::where('academic_year_id', $activeYear->id)
            ->with(['subject', 'teacher', 'rombel', 'room'])
            ->get();

        $periods = \App\Models\Period::orderBy('period_number')->get();

        $entities = [];
        $title = '';
        if ($filterType === 'rombel') {
            $entities = Rombel::orderBy('name')->get();
            $title = "Semua Rombongan Belajar (Kelas)";
            $filename = "jadwal_keseluruhan_per_kelas.xls";
        } elseif ($filterType === 'teacher') {
            $entities = Teacher::orderBy('name')->get();
            $title = "Semua Guru";
            $filename = "jadwal_keseluruhan_per_guru.xls";
        } elseif ($filterType === 'room') {
            $entities = Room::orderBy('code')->get();
            $title = "Semua Ruangan";
            $filename = "jadwal_keseluruhan_per_ruangan.xls";
        }

        return response()->view('schedules.excel', compact(
            'activeYear',
            'filterType',
            'schedules',
            'periods',
            'entities',
            'title'
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
        GenerateScheduleJob::dispatch($activeYear->id, $job->id, Auth::user()->school_id, [
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
