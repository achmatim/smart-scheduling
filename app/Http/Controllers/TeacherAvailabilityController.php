<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\TeacherAvailability;
use Illuminate\Http\Request;

class TeacherAvailabilityController extends Controller
{
    /**
     * Display the availability grid for teachers.
     */
    public function index(Request $request)
    {
        $teachers = Teacher::orderBy('name')->get();
        $selectedTeacherId = $request->get('teacher_id');

        if (!$selectedTeacherId && $teachers->isNotEmpty()) {
            $selectedTeacherId = $teachers->first()->id;
        }

        $availabilities = [];
        $selectedTeacher = null;

        if ($selectedTeacherId) {
            $selectedTeacher = Teacher::findOrFail($selectedTeacherId);
            
            // Fetch availabilities
            $dbAvail = TeacherAvailability::where('teacher_id', $selectedTeacherId)->get();
            
            // Build a grid for easier view rendering [day][period] = boolean
            foreach ($dbAvail as $av) {
                $availabilities[$av->day_of_week][$av->period_number] = $av->is_available;
            }
        }

        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat'
        ];

        return view('availabilities.index', compact('teachers', 'selectedTeacherId', 'selectedTeacher', 'availabilities', 'days'));
    }

    /**
     * Update availability grid for a teacher.
     */
    public function update(Request $request, $teacherId)
    {
        $teacher = Teacher::findOrFail($teacherId);
        
        // Incoming input: slots[day][period] = "1"
        $slots = $request->input('slots', []);

        // Retrieve all availabilities for this teacher
        $dbAvail = TeacherAvailability::where('teacher_id', $teacherId)->get();

        foreach ($dbAvail as $av) {
            $day = $av->day_of_week;
            $period = $av->period_number;

            // Check if this slot was selected/checked
            $isAvailable = isset($slots[$day][$period]);
            
            $av->update(['is_available' => $isAvailable]);
        }

        return redirect()->route('availabilities.index', ['teacher_id' => $teacherId])
            ->with('success', "Konstrain ketersediaan waktu untuk {$teacher->name} berhasil diperbarui.");
    }
}
