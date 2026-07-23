<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\Lesson;
use App\Models\TeacherAvailability;
use App\Models\School;
use App\Models\Rombel;
use App\Models\Subject;
use App\Services\SchedulingEngine;
use App\Services\TenantManager;
use Database\Seeders\SchoolDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Run seeder to seed all 3 schools and admins
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // 2. Set context to SMP Manggala for the unit tests
        $school = School::where('name', 'SMP Manggala')->first();
        TenantManager::setSchoolId($school->id);
    }

    protected function tearDown(): void
    {
        // Clear context
        TenantManager::setSchoolId(null);

        parent::tearDown();
    }

    /**
     * Test database load and session splitting.
     */
    public function test_scheduling_engine_loads_data_correctly(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        // 2. Instantiate engine
        $engine = new SchedulingEngine($activeYear->id);
        $engine->loadData();

        // 3. Reflect on private properties to check load
        $refEngine = new \ReflectionClass(SchedulingEngine::class);
        
        $sessionsProp = $refEngine->getProperty('sessions');
        $sessionsProp->setAccessible(true);
        $sessions = $sessionsProp->getValue($engine);

        $roomsProp = $refEngine->getProperty('rooms');
        $roomsProp->setAccessible(true);
        $rooms = $roomsProp->getValue($engine);

        $teacherAvailProp = $refEngine->getProperty('teacherAvail');
        $teacherAvailProp->setAccessible(true);
        $teacherAvail = $teacherAvailProp->getValue($engine);

        // 6 Rombels, each has 23 JP.
        // Total sessions = 11 * 6 = 66 sessions.
        $this->assertCount(66, $sessions);
        $this->assertCount(9, $rooms); // 9 rooms seeded
        $this->assertCount(9, $teacherAvail); // 9 teachers availability seeded
    }

    /**
     * Test fitness evaluation with teacher and rombel conflicts.
     */
    public function test_fitness_evaluation_detects_clashes(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        $engine = new SchedulingEngine($activeYear->id);
        $engine->loadData();

        $refEngine = new \ReflectionClass(SchedulingEngine::class);
        
        $sessionsProp = $refEngine->getProperty('sessions');
        $sessionsProp->setAccessible(true);
        $sessions = $sessionsProp->getValue($engine);

        // Let's build a chromosome where EVERY session is scheduled at Day 1, Start Period 1, Room 1.
        $clashingChromosome = [];
        $firstRoom = Room::first();

        foreach ($sessions as $session) {
            $clashingChromosome[$session['session_index']] = [
                'day' => 1,
                'start_period' => 1,
                'room_id' => $firstRoom->id,
            ];
        }

        // Evaluate fitness
        $eval = $engine->evaluateFitness($clashingChromosome);

        // Clashes must be detected
        $this->assertGreaterThan(0, $eval['conflicts']);
        $this->assertLessThan(1.0, $eval['fitness']);
    }

    /**
     * Test that teacher availability constraint is strictly satisfied.
     */
    public function test_teacher_availability_constraint_is_never_violated(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        // Let's pick a teacher and make them unavailable on Day 1, periods 6, 7, and 8.
        $teacher = Teacher::first();
        TeacherAvailability::where('teacher_id', $teacher->id)
            ->where('day_of_week', 1)
            ->whereIn('period_number', [6, 7, 8])
            ->update(['is_available' => false]);

        $engine = new SchedulingEngine($activeYear->id);
        $engine->loadData();

        // 1. Verify via Reflection that $teacherValidSlots does not contain periods 6, 7, 8 for duration 1 or 2
        $refEngine = new \ReflectionClass(SchedulingEngine::class);
        $slotsProp = $refEngine->getProperty('teacherValidSlots');
        $slotsProp->setAccessible(true);
        $teacherValidSlots = $slotsProp->getValue($engine);

        $this->assertArrayHasKey($teacher->id, $teacherValidSlots);

        // For duration 2 on Day 1:
        $dur2Slots = $teacherValidSlots[$teacher->id][2] ?? [];
        foreach ($dur2Slots as $slot) {
            if ($slot['day'] === 1) {
                $this->assertNotContains($slot['start_period'], [5, 6, 7, 8]);
            }
        }

        // 2. Run the engine's initializePopulation and verify that in ALL chromosomes,
        // this teacher is never scheduled at unavailable periods.
        $initPopProp = $refEngine->getMethod('initializePopulation');
        $initPopProp->setAccessible(true);
        $population = $initPopProp->invoke($engine);

        $sessionsProp = $refEngine->getProperty('sessions');
        $sessionsProp->setAccessible(true);
        $sessions = $sessionsProp->getValue($engine);

        foreach ($population as $chromosome) {
            foreach ($sessions as $session) {
                if ($session['teacher_id'] === $teacher->id) {
                    $gene = $chromosome[$session['session_index']];
                    if ($gene['day'] === 1) {
                        $start = $gene['start_period'];
                        $end = $start + $session['duration'] - 1;
                        for ($p = $start; $p <= $end; $p++) {
                            $this->assertNotContains($p, [6, 7, 8], "Teacher scheduled at unavailable period {$p} on Day 1");
                        }
                    }
                }
            }
        }
    }

    /**
     * Test that Rombel designated room is respected for general subjects.
     */
    public function test_rombel_designated_room_is_respected_for_general_subjects(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        $engine = new SchedulingEngine($activeYear->id);
        $engine->loadData();

        $refEngine = new \ReflectionClass(SchedulingEngine::class);
        
        $sessionsProp = $refEngine->getProperty('sessions');
        $sessionsProp->setAccessible(true);
        $sessions = $sessionsProp->getValue($engine);

        $getValidRoomsMethod = $refEngine->getMethod('getValidRoomsForSession');
        $getValidRoomsMethod->setAccessible(true);

        foreach ($sessions as $session) {
            $rombel = Rombel::find($session['rombel_id']);
            $validRooms = $getValidRoomsMethod->invokeArgs($engine, [$session]);

            if ($session['subject_type'] === 'umum') {
                $this->assertCount(1, $validRooms);
                $this->assertEquals($rombel->room_id, $validRooms[0]);
            }
        }
    }

    /**
     * Test that same-day subject constraint is detected and penalized.
     */
    public function test_same_day_subject_constraint_is_enforced(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        $engine = new SchedulingEngine($activeYear->id);
        $engine->loadData();

        $refEngine = new \ReflectionClass(SchedulingEngine::class);
        
        $sessionsProp = $refEngine->getProperty('sessions');
        $sessionsProp->setAccessible(true);
        $sessions = $sessionsProp->getValue($engine);

        $sameSubjectSessions = [];

        foreach ($sessions as $session) {
            $key = "{$session['rombel_id']}-{$session['subject_id']}";
            $sameSubjectSessions[$key][] = $session;
        }

        $chosenPair = [];
        foreach ($sameSubjectSessions as $key => $list) {
            if (count($list) >= 2) {
                $chosenPair = [$list[0], $list[1]];
                break;
            }
        }

        $this->assertNotEmpty($chosenPair, "Must have at least one split subject with multiple sessions");

        $chromosome = [];
        $firstRoom = Room::first();

        foreach ($sessions as $session) {
            $chromosome[$session['session_index']] = [
                'day' => ($session['session_index'] % 5) + 1,
                'start_period' => 1,
                'room_id' => $firstRoom->id,
            ];
        }

        $chromosome[$chosenPair[0]['session_index']] = [
            'day' => 1,
            'start_period' => 1,
            'room_id' => $firstRoom->id,
        ];
        $chromosome[$chosenPair[1]['session_index']] = [
            'day' => 1,
            'start_period' => 3,
            'room_id' => $firstRoom->id,
        ];

        // Evaluate fitness
        $eval = $engine->evaluateFitness($chromosome);

        $this->assertGreaterThan(0, $eval['conflicts'], "Same-day subject duplicate not detected as conflict");
    }

    /**
     * Test that same-day teacher constraint is detected and penalized as soft conflict.
     */
    public function test_same_day_teacher_constraint_is_penalized(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        $engine = new SchedulingEngine($activeYear->id);
        $engine->loadData();

        $refEngine = new \ReflectionClass(SchedulingEngine::class);
        
        $firstTeacher = Teacher::orderBy('id', 'asc')->first();
        $firstRombel = Rombel::orderBy('id', 'asc')->first();
        $subjects = Subject::orderBy('id', 'asc')->take(2)->get();
        $firstRoom = Room::orderBy('id', 'asc')->first();

        $mockSessions = [
            [
                'session_index' => 0,
                'lesson_id' => 1,
                'rombel_id' => $firstRombel->id,
                'subject_id' => $subjects[0]->id,
                'teacher_id' => $firstTeacher->id,
                'duration' => 2,
                'subject_type' => 'umum',
            ],
            [
                'session_index' => 1,
                'lesson_id' => 2,
                'rombel_id' => $firstRombel->id,
                'subject_id' => $subjects[1]->id,
                'teacher_id' => $firstTeacher->id,
                'duration' => 2,
                'subject_type' => 'umum',
            ]
        ];

        $sessionsProp = $refEngine->getProperty('sessions');
        $sessionsProp->setAccessible(true);
        $sessionsProp->setValue($engine, $mockSessions);

        $chromosome = [
            0 => [
                'day' => 2,
                'start_period' => 1,
                'room_id' => $firstRombel->room_id ?? $firstRoom->id,
            ],
            1 => [
                'day' => 2,
                'start_period' => 5,
                'room_id' => $firstRombel->room_id ?? $firstRoom->id,
            ]
        ];

        // Evaluate fitness
        $eval = $engine->evaluateFitness($chromosome);

        $this->assertEquals(0, $eval['conflicts']);
        $this->assertGreaterThan(0, $eval['soft_conflicts']);
    }
}
