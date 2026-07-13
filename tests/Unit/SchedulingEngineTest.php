<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\Lesson;
use App\Models\TeacherAvailability;
use App\Services\SchedulingEngine;
use Database\Seeders\SchoolDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulingEngineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test database load and session splitting.
     */
    public function test_scheduling_engine_loads_data_correctly(): void
    {
        // 1. Run seeder
        $this->seed(SchoolDataSeeder::class);

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

        // 6 Rombels, each has 23 JP. Some split into 2 JP, some into 3 JP.
        // Let's count sessions. 
        // Per rombel: 
        // - MAT (4 JP split 2,2) -> 2 sessions
        // - ING (4 JP split 2,2) -> 2 sessions
        // - IPA (4 JP split 2,2) -> 2 sessions
        // - IND (4 JP split 2,2) -> 2 sessions
        // - IPS (3 JP split 3) -> 1 session
        // - PJK (2 JP split 2) -> 1 session
        // - INF (2 JP split 2) -> 1 session
        // Total = 2 + 2 + 2 + 2 + 1 + 1 + 1 = 11 sessions per rombel.
        // With 6 rombels, total sessions = 11 * 6 = 66 sessions.
        $this->assertCount(66, $sessions);
        $this->assertCount(9, $rooms); // 9 rooms seeded
        $this->assertCount(9, $teacherAvail); // 9 teachers availability seeded
    }

    /**
     * Test fitness evaluation with teacher and rombel conflicts.
     */
    public function test_fitness_evaluation_detects_clashes(): void
    {
        $this->seed(SchoolDataSeeder::class);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $engine = new SchedulingEngine($activeYear->id);
        $engine->loadData();

        $refEngine = new \ReflectionClass(SchedulingEngine::class);
        
        $sessionsProp = $refEngine->getProperty('sessions');
        $sessionsProp->setAccessible(true);
        $sessions = $sessionsProp->getValue($engine);

        // Let's build a chromosome where EVERY session is scheduled at Day 1, Start Period 1, Room 1.
        // This is a worst-case scenario with massive clashing.
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
        $this->seed(SchoolDataSeeder::class);
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
        // Periods 6, 7, 8 are unavailable.
        // Valid starts (from validStarts for duration 2):
        // Break is period 5 (istirahat) and 8 (istirahat).
        // Let's check which starts on Day 1 are in teacherValidSlots for duration 2.
        // It must NOT contain start_period 5 (break), 6 (6-7 overlaps with 6,7 unavailable), 7 (7-8 overlaps with 7,8 unavailable), 8 (break).
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
}
