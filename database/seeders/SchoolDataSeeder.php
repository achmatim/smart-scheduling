<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Room;
use App\Models\Rombel;
use App\Models\TeacherAvailability;
use App\Models\Lesson;
use App\Models\Period;
use Illuminate\Database\Seeder;

class SchoolDataSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Periods / Jam Kerja
        $periodsData = [
            ['period_number' => 1, 'start_time' => '07:15', 'end_time' => '07:55', 'is_break' => false],
            ['period_number' => 2, 'start_time' => '07:55', 'end_time' => '08:35', 'is_break' => false],
            ['period_number' => 3, 'start_time' => '08:35', 'end_time' => '09:15', 'is_break' => false],
            ['period_number' => 4, 'start_time' => '09:15', 'end_time' => '09:30', 'is_break' => true],  // Istirahat
            ['period_number' => 5, 'start_time' => '09:30', 'end_time' => '10:10', 'is_break' => false],
            ['period_number' => 6, 'start_time' => '10:10', 'end_time' => '10:50', 'is_break' => false],
            ['period_number' => 7, 'start_time' => '10:50', 'end_time' => '11:05', 'is_break' => true],  // Istirahat
            ['period_number' => 8, 'start_time' => '11:05', 'end_time' => '11:45', 'is_break' => false],
            ['period_number' => 9, 'start_time' => '11:45', 'end_time' => '12:25', 'is_break' => false],
            ['period_number' => 10, 'start_time' => '12:25', 'end_time' => '13:05', 'is_break' => false],
        ];

        foreach ($periodsData as $p) {
            Period::create($p);
        }

        // 1. Academic Year
        $ay = AcademicYear::create([
            'year' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
            'is_locked' => false,
        ]);

        // 2. Teachers
        $teachersData = [
            ['nip' => '198001012005011001', 'name' => 'Drs. Budi Santoso', 'email' => 'budi@manggala.sch.id', 'phone' => '081234567890'],
            ['nip' => '198502022008012002', 'name' => 'Siti Aminah, S.Pd.', 'email' => 'siti@manggala.sch.id', 'phone' => '081234567891'],
            ['nip' => '199003032015011003', 'name' => 'Joko Susilo, M.Pd.', 'email' => 'joko@manggala.sch.id', 'phone' => '081234567892'],
            ['nip' => '199204042018012004', 'name' => 'Dewi Lestari, S.Si.', 'email' => 'dewi@manggala.sch.id', 'phone' => '081234567893'],
            ['nip' => '198805052012011005', 'name' => 'Ahmad Fauzi, S.Pd.', 'email' => 'ahmad@manggala.sch.id', 'phone' => '081234567894'],
            ['nip' => '199506062020012006', 'name' => 'Rina Wijaya, M.A.', 'email' => 'rina@manggala.sch.id', 'phone' => '081234567895'],
            ['nip' => '197807072003011007', 'name' => 'Hendra Wijaya, S.Or.', 'email' => 'hendra@manggala.sch.id', 'phone' => '081234567896'],
            ['nip' => '198308082009011008', 'name' => 'Dr. Wawan Hermawan', 'email' => 'wawan@manggala.sch.id', 'phone' => '081234567897'],
            ['nip' => '199109092017012009', 'name' => 'Sari Astuti, S.Kom.', 'email' => 'sari@manggala.sch.id', 'phone' => '081234567898'],
        ];

        $teachers = [];
        foreach ($teachersData as $t) {
            $teachers[] = Teacher::create($t);
        }

        // 3. Subjects
        $subjectsData = [
            ['code' => 'MAT', 'name' => 'Matematika', 'type' => 'umum'],
            ['code' => 'ING', 'name' => 'Bahasa Inggris', 'type' => 'umum'],
            ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam', 'type' => 'praktek'],
            ['code' => 'IND', 'name' => 'Bahasa Indonesia', 'type' => 'umum'],
            ['code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial', 'type' => 'umum'],
            ['code' => 'PJK', 'name' => 'Pendidikan Jasmani & Kesehatan', 'type' => 'olahraga'],
            ['code' => 'INF', 'name' => 'Informatika', 'type' => 'praktek'],
        ];

        $subjects = [];
        foreach ($subjectsData as $s) {
            $subjects[$s['code']] = Subject::create($s);
        }

        // 4. Rooms
        $roomsData = [
            ['code' => 'R-7A', 'name' => 'Kelas VII A', 'type' => 'umum', 'capacity' => 32],
            ['code' => 'R-7B', 'name' => 'Kelas VII B', 'type' => 'umum', 'capacity' => 32],
            ['code' => 'R-8A', 'name' => 'Kelas VIII A', 'type' => 'umum', 'capacity' => 32],
            ['code' => 'R-8B', 'name' => 'Kelas VIII B', 'type' => 'umum', 'capacity' => 32],
            ['code' => 'R-9A', 'name' => 'Kelas IX A', 'type' => 'umum', 'capacity' => 32],
            ['code' => 'R-9B', 'name' => 'Kelas IX B', 'type' => 'umum', 'capacity' => 32],
            ['code' => 'LAB-IPA', 'name' => 'Laboratorium IPA', 'type' => 'lab', 'capacity' => 36],
            ['code' => 'LAB-KOM', 'name' => 'Laboratorium Komputer', 'type' => 'lab', 'capacity' => 36],
            ['code' => 'LAP-OLA', 'name' => 'Lapangan Olahraga', 'type' => 'lapangan', 'capacity' => 100],
        ];

        foreach ($roomsData as $r) {
            Room::create($r);
        }

        // 5. Rombels
        $rombelsData = [
            ['name' => 'VII A', 'grade' => 7],
            ['name' => 'VII B', 'grade' => 7],
            ['name' => 'VIII A', 'grade' => 8],
            ['name' => 'VIII B', 'grade' => 8],
            ['name' => 'IX A', 'grade' => 9],
            ['name' => 'IX B', 'grade' => 9],
        ];

        $rombels = [];
        foreach ($rombelsData as $rm) {
            $rombels[] = Rombel::create($rm);
        }

        // 6. Teacher Availabilities
        // Monday-Friday (1-5), periods from database.
        // We will seed all slots as available = true, except specific exclusions.
        $allPeriods = Period::all();
        foreach ($teachers as $teacher) {
            $availabilities = [];
            for ($day = 1; $day <= 5; $day++) {
                foreach ($allPeriods as $pModel) {
                    $period = $pModel->period_number;
                    $isAvailable = true;

                    // Constraints to test algorithm:
                    // Drs. Budi Santoso (Teacher id 1) is busy on Monday (day 1), periods 1-4
                    if ($teacher->id === 1 && $day === 1 && $period <= 4) {
                        $isAvailable = false;
                    }
                    // Siti Aminah (Teacher id 2) is busy on Friday (day 5), periods 8-10
                    if ($teacher->id === 2 && $day === 5 && $period >= 8) {
                        $isAvailable = false;
                    }
                    // Hendra Wijaya (Teacher id 7 - Sports) is busy on Tuesday (day 2), periods 1-2
                    if ($teacher->id === 7 && $day === 2 && $period <= 2) {
                        $isAvailable = false;
                    }

                    $availabilities[] = [
                        'teacher_id' => $teacher->id,
                        'day_of_week' => $day,
                        'period_number' => $period,
                        'is_available' => $isAvailable,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            TeacherAvailability::insert($availabilities);
        }

        // 7. Lessons / Teaching assignments
        // Teacher mappings:
        // Teacher 1 (Budi) -> Math for VII A & VII B
        // Teacher 2 (Siti) -> English for VII A & VII B
        // Teacher 3 (Joko) -> Science for VIII A & VIII B
        // Teacher 4 (Dewi) -> Math for VIII A & VIII B
        // Teacher 5 (Ahmad) -> Indonesian for IX A & IX B
        // Teacher 6 (Rina) -> English for IX A & IX B
        // Teacher 7 (Hendra) -> Sports for all rombels (VII A, VII B, VIII A, VIII B, IX A, IX B)
        // Teacher 8 (Wawan) -> IPS for all rombels
        // Teacher 9 (Sari) -> Informatics for all rombels

        // Let's create assignments:
        // For each class, assign the subjects:
        foreach ($rombels as $rombel) {
            $grade = $rombel->grade;

            // Math
            $mathTeacher = ($grade == 7) ? $teachers[0] : (($grade == 8) ? $teachers[3] : $teachers[0]); // Budi or Dewi
            Lesson::create([
                'academic_year_id' => $ay->id,
                'rombel_id' => $rombel->id,
                'subject_id' => $subjects['MAT']->id,
                'teacher_id' => $mathTeacher->id,
                'total_hours' => 4,
                'split_hours' => '2,2'
            ]);

            // English
            $engTeacher = ($grade == 7) ? $teachers[1] : (($grade == 9) ? $teachers[5] : $teachers[1]); // Siti or Rina
            Lesson::create([
                'academic_year_id' => $ay->id,
                'rombel_id' => $rombel->id,
                'subject_id' => $subjects['ING']->id,
                'teacher_id' => $engTeacher->id,
                'total_hours' => 4,
                'split_hours' => '2,2'
            ]);

            // Science (IPA)
            Lesson::create([
                'academic_year_id' => $ay->id,
                'rombel_id' => $rombel->id,
                'subject_id' => $subjects['IPA']->id,
                'teacher_id' => $teachers[2]->id, // Joko
                'total_hours' => 4,
                'split_hours' => '2,2'
            ]);

            // Indonesian (IND)
            Lesson::create([
                'academic_year_id' => $ay->id,
                'rombel_id' => $rombel->id,
                'subject_id' => $subjects['IND']->id,
                'teacher_id' => $teachers[4]->id, // Ahmad
                'total_hours' => 4,
                'split_hours' => '2,2'
            ]);

            // IPS
            Lesson::create([
                'academic_year_id' => $ay->id,
                'rombel_id' => $rombel->id,
                'subject_id' => $subjects['IPS']->id,
                'teacher_id' => $teachers[7]->id, // Wawan
                'total_hours' => 3,
                'split_hours' => '3'
            ]);

            // Sports (PJK)
            Lesson::create([
                'academic_year_id' => $ay->id,
                'rombel_id' => $rombel->id,
                'subject_id' => $subjects['PJK']->id,
                'teacher_id' => $teachers[6]->id, // Hendra
                'total_hours' => 2,
                'split_hours' => '2'
            ]);

            // Informatics (INF)
            Lesson::create([
                'academic_year_id' => $ay->id,
                'rombel_id' => $rombel->id,
                'subject_id' => $subjects['INF']->id,
                'teacher_id' => $teachers[8]->id, // Sari
                'total_hours' => 2,
                'split_hours' => '2'
            ]);
        }
    }
}
