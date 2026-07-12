<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Lesson;
use App\Models\Rombel;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index()
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            return redirect()->route('dashboard')->with('error', 'Silakan aktifkan Tahun Akademik terlebih dahulu.');
        }

        $lessons = Lesson::where('academic_year_id', $activeYear->id)
            ->with(['rombel', 'subject', 'teacher'])
            ->get();

        $rombels = Rombel::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = Teacher::orderBy('name')->get();

        $isLocked = $activeYear->is_locked;

        return view('lessons.index', compact('activeYear', 'lessons', 'rombels', 'subjects', 'teachers', 'isLocked'));
    }

    public function store(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tidak ada tahun akademik aktif.');
        }

        if ($activeYear->is_locked) {
            return redirect()->back()->with('error', 'Tahun akademik ini telah dikunci. Alokasi tidak bisa diubah.');
        }

        $request->validate([
            'rombel_id' => 'required|exists:rombels,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'total_hours' => 'required|integer|min:1|max:10',
            'split_hours' => 'required|string|regex:/^\d+(,\d+)*$/', // e.g. 2,2 or 3 or 2,1
        ]);

        // Verify that sum of split hours matches total hours
        $splits = explode(',', $request->split_hours);
        $sum = array_sum(array_map('intval', $splits));
        if ($sum !== (int)$request->total_hours) {
            return redirect()->back()->withInput()->with('error', 'Jumlah pemecahan jam (split hours) harus sama dengan total jam pelajaran.');
        }

        // Check if lesson allocation already exists for this rombel & subject
        $exists = Lesson::where('academic_year_id', $activeYear->id)
            ->where('rombel_id', $request->rombel_id)
            ->where('subject_id', $request->subject_id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->with('error', 'Alokasi mata pelajaran untuk kelas tersebut sudah terdaftar.');
        }

        Lesson::create([
            'academic_year_id' => $activeYear->id,
            'rombel_id' => $request->rombel_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'total_hours' => $request->total_hours,
            'split_hours' => $request->split_hours,
        ]);

        return redirect()->route('lessons.index')->with('success', 'Alokasi mengajar berhasil ditambahkan.');
    }

    public function update(Request $request, Lesson $lesson)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear->is_locked) {
            return redirect()->back()->with('error', 'Tahun akademik ini telah dikunci. Alokasi tidak bisa diubah.');
        }

        $request->validate([
            'rombel_id' => 'required|exists:rombels,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'total_hours' => 'required|integer|min:1|max:10',
            'split_hours' => 'required|string|regex:/^\d+(,\d+)*$/',
        ]);

        $splits = explode(',', $request->split_hours);
        $sum = array_sum(array_map('intval', $splits));
        if ($sum !== (int)$request->total_hours) {
            return redirect()->back()->withInput()->with('error', 'Jumlah pemecahan jam (split hours) harus sama dengan total jam pelajaran.');
        }

        // Check if lesson allocation already exists for this rombel & subject (excluding this lesson)
        $exists = Lesson::where('academic_year_id', $lesson->academic_year_id)
            ->where('rombel_id', $request->rombel_id)
            ->where('subject_id', $request->subject_id)
            ->where('id', '!=', $lesson->id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->with('error', 'Alokasi mata pelajaran untuk kelas tersebut sudah terdaftar.');
        }

        $lesson->update([
            'rombel_id' => $request->rombel_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'total_hours' => $request->total_hours,
            'split_hours' => $request->split_hours,
        ]);

        return redirect()->route('lessons.index')->with('success', 'Alokasi mengajar berhasil diperbarui.');
    }

    public function destroy(Lesson $lesson)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear->is_locked) {
            return redirect()->back()->with('error', 'Tahun akademik ini telah dikunci. Alokasi tidak bisa diubah.');
        }

        $lesson->delete();
        return redirect()->route('lessons.index')->with('success', 'Alokasi mengajar berhasil dihapus.');
    }
}
