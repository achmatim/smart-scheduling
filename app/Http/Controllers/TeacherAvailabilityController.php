<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\Period;
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

        // Fetch dynamic periods
        $periods = Period::orderBy('period_number')->get();

        return view('availabilities.index', compact('teachers', 'selectedTeacherId', 'selectedTeacher', 'availabilities', 'days', 'periods'));
    }

    /**
     * Update availability grid for a teacher.
     */
    public function update(Request $request, $teacherId)
    {
        $teacher = Teacher::findOrFail($teacherId);
        
        // Incoming input: slots[day][period] = "1"
        $slots = $request->input('slots', []);

        // Fetch all dynamic periods
        $periods = Period::orderBy('period_number')->get();

        // Save availabilities dynamically for all days and periods
        for ($day = 1; $day <= 5; $day++) {
            foreach ($periods as $p) {
                $period = $p->period_number;
                
                // If it is a break period, it should default to not available (false)
                $isAvailable = $p->is_break ? false : isset($slots[$day][$period]);

                TeacherAvailability::updateOrCreate(
                    [
                        'teacher_id' => $teacherId,
                        'day_of_week' => $day,
                        'period_number' => $period,
                    ],
                    [
                        'is_available' => $isAvailable,
                    ]
                );
            }
        }

        return redirect()->route('availabilities.index', ['teacher_id' => $teacherId])
            ->with('success', "Konstrain ketersediaan waktu untuk {$teacher->name} berhasil diperbarui.");
    }
}
