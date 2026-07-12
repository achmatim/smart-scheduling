<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Lesson;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\TeacherAvailability;
use App\Models\SchedulingJob;
use Exception;
use Illuminate\Support\Facades\Log;

class SchedulingEngine
{
    // GA Configuration
    private $popSize = 100;
    private $maxGenerations = 100;
    private $crossoverRate = 0.8;
    private $mutationRate = 0.15;
    private $elitismCount = 5;

    // Data lists
    private $academicYearId;
    private $sessions = []; // All sessions (genes) to schedule
    private $rooms = []; // All rooms
    private $teacherAvail = []; // Fast lookup teacher availability
    private $validRoomsPerSubjectType = [];

    // Working days and periods
    private $maxDays = 5; // Monday - Friday (1 - 5)
    private $maxPeriods = 8; // Max daily period number (loaded dynamically)
    private $breakPeriods = []; // List of break period numbers
    private $validStarts = []; // Precalculated valid start periods for durations

    public function __construct(int $academicYearId, array $config = [])
    {
        $this->academicYearId = $academicYearId;
        $this->popSize = $config['pop_size'] ?? $this->popSize;
        $this->maxGenerations = $config['max_generations'] ?? $this->maxGenerations;
        $this->crossoverRate = $config['crossover_rate'] ?? $this->crossoverRate;
        $this->mutationRate = $config['mutation_rate'] ?? $this->mutationRate;
        $this->elitismCount = $config['elitism_count'] ?? $this->elitismCount;
    }

    /**
     * Load master data and constraints from the database.
     */
    public function loadData()
    {
        // Reset arrays to prevent duplicate loading on multiple calls
        $this->sessions = [];
        $this->rooms = [];
        $this->teacherAvail = [];
        $this->validRoomsPerSubjectType = [];

        // Load active academic year
        $ay = AcademicYear::findOrFail($this->academicYearId);
        if ($ay->is_locked) {
            throw new Exception("Jadwal untuk Semester ini telah dikunci (Locked).");
        }

        // Load dynamic periods configuration
        $this->maxPeriods = \App\Models\Period::max('period_number') ?? 8;
        $this->breakPeriods = \App\Models\Period::where('is_break', true)->pluck('period_number')->toArray();

        // Pre-calculate valid start periods for each duration (1 to 10 JP)
        for ($d = 1; $d <= 10; $d++) {
            $this->validStarts[$d] = [];
            for ($s = 1; $s <= $this->maxPeriods - $d + 1; $s++) {
                $overlapsBreak = false;
                for ($offset = 0; $offset < $d; $offset++) {
                    if (in_array($s + $offset, $this->breakPeriods)) {
                        $overlapsBreak = true;
                        break;
                    }
                }
                if (!$overlapsBreak) {
                    $this->validStarts[$d][] = $s;
                }
            }
            // Fallback to all periods if no slot was found (should not happen in normal configuration)
            if (empty($this->validStarts[$d])) {
                for ($s = 1; $s <= $this->maxPeriods - $d + 1; $s++) {
                    $this->validStarts[$d][] = $s;
                }
            }
        }

        // Load rooms
        $allRooms = Room::all();
        foreach ($allRooms as $room) {
            $this->rooms[$room->id] = [
                'id' => $room->id,
                'code' => $room->code,
                'name' => $room->name,
                'type' => $room->type, // umum, lab, lapangan
            ];
        }

        // Categorize rooms for fast CSP lookup
        $this->validRoomsPerSubjectType = [
            'umum' => $allRooms->whereIn('type', ['umum', 'lab'])->pluck('id')->toArray(),
            'lab' => $allRooms->where('type', 'lab')->pluck('id')->toArray(),
            'olahraga' => $allRooms->where('type', 'lapangan')->pluck('id')->toArray(),
        ];

        // Load teacher availability
        $availabilities = TeacherAvailability::all();
        foreach ($availabilities as $av) {
            $this->teacherAvail[$av->teacher_id][$av->day_of_week][$av->period_number] = $av->is_available;
        }

        // Load lessons and split them into sessions
        $lessons = Lesson::where('academic_year_id', $this->academicYearId)->get();
        if ($lessons->isEmpty()) {
            throw new Exception("Tidak ada data alokasi mengajar (Lesson) untuk semester ini.");
        }

        $sessionId = 0;
        foreach ($lessons as $lesson) {
            // Get split hours, e.g. "2,2" or "3"
            $splits = explode(',', $lesson->split_hours);
            foreach ($splits as $duration) {
                $duration = (int) trim($duration);
                if ($duration <= 0) continue;

                $this->sessions[] = [
                    'session_index' => $sessionId++,
                    'lesson_id' => $lesson->id,
                    'rombel_id' => $lesson->rombel_id,
                    'subject_id' => $lesson->subject_id,
                    'teacher_id' => $lesson->teacher_id,
                    'duration' => $duration,
                    'subject_type' => $lesson->subject->type,
                ];
            }
        }
    }

    /**
     * Run the Genetic Algorithm.
     * Updates $jobModel status and progress in real-time.
     */
    public function run(SchedulingJob $jobModel)
    {
        $this->loadData();

        if (empty($this->sessions)) {
            throw new Exception("Tidak ada sesi kelas yang perlu dijadwalkan.");
        }

        $jobModel->update([
            'status' => 'running',
            'progress' => 0,
            'max_generations' => $this->maxGenerations,
            'current_generation' => 0,
        ]);

        // 1. Initialize Population (with CSP Heuristic)
        $population = $this->initializePopulation();

        $bestChromosome = null;
        $bestFitness = -1;
        $bestConflicts = 9999;

        // 2. Evolution Loop
        for ($generation = 1; $generation <= $this->maxGenerations; $generation++) {
            // Evaluate fitness
            $evaluatedPop = [];
            foreach ($population as $chromosome) {
                $eval = $this->evaluateFitness($chromosome);
                $evaluatedPop[] = [
                    'chromosome' => $chromosome,
                    'fitness' => $eval['fitness'],
                    'conflicts' => $eval['conflicts'],
                ];

                if ($eval['fitness'] > $bestFitness) {
                    $bestFitness = $eval['fitness'];
                    $bestConflicts = $eval['conflicts'];
                    $bestChromosome = $chromosome;
                }
            }

            // Report progress
            $progress = (int) (($generation / $this->maxGenerations) * 100);
            $jobModel->update([
                'progress' => $progress,
                'current_generation' => $generation,
                'best_fitness' => $bestFitness,
                'conflicts' => $bestConflicts,
            ]);

            // If we found a perfect solution (0 conflicts), we can stop early!
            if ($bestConflicts === 0) {
                Log::info("Solusi optimal tanpa konflik ditemukan pada generasi ke-$generation!");
                break;
            }

            // Sort population by fitness descending
            usort($evaluatedPop, function ($a, $b) {
                return $b['fitness'] <=> $a['fitness'];
            });

            // 3. Selection & Reproduction
            $nextPopulation = [];

            // Elitism: carry over the best ones unchanged
            for ($i = 0; $i < $this->elitismCount; $i++) {
                $nextPopulation[] = $evaluatedPop[$i]['chromosome'];
            }

            // Generate rest of the population
            while (count($nextPopulation) < $this->popSize) {
                // Select parents
                $parent1 = $this->tournamentSelect($evaluatedPop);
                $parent2 = $this->tournamentSelect($evaluatedPop);

                // Crossover
                if (mt_rand() / mt_getrandmax() < $this->crossoverRate) {
                    $children = $this->crossover($parent1, $parent2);
                    $child1 = $children[0];
                    $child2 = $children[1];
                } else {
                    $child1 = $parent1;
                    $child2 = $parent2;
                }

                // Mutation
                $child1 = $this->mutate($child1);
                $child2 = $this->mutate($child2);

                $nextPopulation[] = $child1;
                if (count($nextPopulation) < $this->popSize) {
                    $nextPopulation[] = $child2;
                }
            }

            $population = $nextPopulation;
        }

        // Save the best schedule to the database
        $this->saveSchedule($bestChromosome);

        $jobModel->update([
            'status' => 'completed',
            'progress' => 100,
            'best_fitness' => $bestFitness,
            'conflicts' => $bestConflicts,
        ]);

        return [
            'fitness' => $bestFitness,
            'conflicts' => $bestConflicts,
        ];
    }

    /**
     * Initialize population.
     * CSP Heuristic: Try to find conflict-free slots during random assignment.
     */
    private function initializePopulation(): array
    {
        $population = [];

        for ($p = 0; $p < $this->popSize; $p++) {
            $chromosome = [];
            
            // To support local conflict tracking during construction
            $teacherGrid = [];
            $rombelGrid = [];
            $roomGrid = [];

            foreach ($this->sessions as $session) {
                $validRooms = $this->validRoomsPerSubjectType[$session['subject_type']] ?? array_keys($this->rooms);
                if (empty($validRooms)) {
                    $validRooms = array_keys($this->rooms);
                }

                $placed = false;
                // CSP Heuristic: Try 15 times to place session in a clash-free slot
                for ($attempt = 0; $attempt < 15; $attempt++) {
                    $day = mt_rand(1, $this->maxDays);
                    // Select a start period that does not overlap with breaks
                    $validStartsForDuration = $this->validStarts[$session['duration']] ?? [1];
                    $startPeriod = $validStartsForDuration[array_rand($validStartsForDuration)];
                    $roomId = $validRooms[array_rand($validRooms)];

                    // Check local clashes with already placed sessions
                    $clash = false;
                    for ($offset = 0; $offset < $session['duration']; $offset++) {
                        $period = $startPeriod + $offset;

                        // Check if period is a break period (CSP check)
                        if (in_array($period, $this->breakPeriods)) {
                            $clash = true;
                            break;
                        }

                        // Check teacher availability constraint
                        $teacherAvailable = $this->teacherAvail[$session['teacher_id']][$day][$period] ?? true;
                        if (!$teacherAvailable) {
                            $clash = true;
                            break;
                        }

                        // Check teacher clash
                        if (isset($teacherGrid[$session['teacher_id']][$day][$period])) {
                            $clash = true;
                            break;
                        }
                        // Check class clash
                        if (isset($rombelGrid[$session['rombel_id']][$day][$period])) {
                            $clash = true;
                            break;
                        }
                        // Check room clash
                        if (isset($roomGrid[$roomId][$day][$period])) {
                            $clash = true;
                            break;
                        }
                    }

                    if (!$clash) {
                        // Place here
                        $chromosome[$session['session_index']] = [
                            'day' => $day,
                            'start_period' => $startPeriod,
                            'room_id' => $roomId,
                        ];

                        // Book the local grid
                        for ($offset = 0; $offset < $session['duration']; $offset++) {
                            $period = $startPeriod + $offset;
                            $teacherGrid[$session['teacher_id']][$day][$period] = true;
                            $rombelGrid[$session['rombel_id']][$day][$period] = true;
                            $roomGrid[$roomId][$day][$period] = true;
                        }
                        $placed = true;
                        break;
                    }
                }

                // If failed to find a conflict-free slot, place randomly
                if (!$placed) {
                    $day = mt_rand(1, $this->maxDays);
                    $validStartsForDuration = $this->validStarts[$session['duration']] ?? [1];
                    $startPeriod = $validStartsForDuration[array_rand($validStartsForDuration)];
                    $roomId = $validRooms[array_rand($validRooms)];

                    $chromosome[$session['session_index']] = [
                        'day' => $day,
                        'start_period' => $startPeriod,
                        'room_id' => $roomId,
                    ];
                }
            }

            $population[] = $chromosome;
        }

        return $population;
    }

    /**
     * Fitness function evaluation.
     * Returns ['fitness' => float, 'conflicts' => int]
     */
    public function evaluateFitness(array $chromosome): array
    {
        $teacherGrid = [];
        $rombelGrid = [];
        $roomGrid = [];

        $hardConflicts = 0;
        $softConflicts = 0;

        foreach ($this->sessions as $session) {
            $gene = $chromosome[$session['session_index']];
            $day = $gene['day'];
            $startPeriod = $gene['start_period'];
            $roomId = $gene['room_id'];
            $duration = $session['duration'];

            // 1. Check Period Overflow (Hard Constraint)
            if ($startPeriod + $duration - 1 > $this->maxPeriods) {
                $hardConflicts += 5; // Large penalty
                continue;
            }

            // 2. Room Type constraint (satisfied by construction, but let's double check)
            $room = $this->rooms[$roomId] ?? null;
            if ($room) {
                if ($session['subject_type'] === 'lab' && $room['type'] !== 'lab') {
                    $hardConflicts += 2;
                }
                if ($session['subject_type'] === 'olahraga' && $room['type'] !== 'lapangan') {
                    $hardConflicts += 2;
                }
            } else {
                $hardConflicts += 5;
            }

            // 3. Grid allocation and overlap checks
            for ($offset = 0; $offset < $duration; $offset++) {
                $period = $startPeriod + $offset;

                // Check Break period constraint (Hard Constraint)
                if (in_array($period, $this->breakPeriods)) {
                    $hardConflicts += 5;
                }

                // A. Teacher availability (Hard Constraint)
                $teacherAvailable = $this->teacherAvail[$session['teacher_id']][$day][$period] ?? true;
                if (!$teacherAvailable) {
                    $hardConflicts++;
                }

                // B. Teacher clash (Hard Constraint)
                if (isset($teacherGrid[$session['teacher_id']][$day][$period])) {
                    $hardConflicts++;
                } else {
                    $teacherGrid[$session['teacher_id']][$day][$period] = $session['session_index'];
                }

                // C. Rombel clash (Hard Constraint)
                if (isset($rombelGrid[$session['rombel_id']][$day][$period])) {
                    $hardConflicts++;
                } else {
                    $rombelGrid[$session['rombel_id']][$day][$period] = $session['session_index'];
                }

                // D. Room clash (Hard Constraint)
                if (isset($roomGrid[$roomId][$day][$period])) {
                    $hardConflicts++;
                } else {
                    $roomGrid[$roomId][$day][$period] = $session['session_index'];
                }
            }
        }

        // --- SOFT CONSTRAINTS ---
        // 1. Teacher Gaps
        // If a teacher teaches multiple sessions in a day, they shouldn't have idle gaps.
        foreach ($teacherGrid as $teacherId => $days) {
            foreach ($days as $day => $periods) {
                if (count($periods) > 1) {
                    $activePeriods = array_keys($periods);
                    sort($activePeriods);
                    $min = min($activePeriods);
                    $max = max($activePeriods);
                    
                    // Count empty periods between min and max active periods
                    for ($p = $min; $p <= $max; $p++) {
                        if (in_array($p, $this->breakPeriods)) continue;
                        if (!isset($periods[$p])) {
                            $softConflicts++;
                        }
                    }
                }
            }
        }

        // 2. Class Gaps (Rombel Gaps)
        // Students shouldn't have idle gaps between their lessons in a day.
        foreach ($rombelGrid as $rombelId => $days) {
            foreach ($days as $day => $periods) {
                if (count($periods) > 1) {
                    $activePeriods = array_keys($periods);
                    sort($activePeriods);
                    $min = min($activePeriods);
                    $max = max($activePeriods);

                    for ($p = $min; $p <= $max; $p++) {
                        if (in_array($p, $this->breakPeriods)) continue;
                        if (!isset($periods[$p])) {
                            $softConflicts++;
                        }
                    }
                }
            }
        }

        // Calculate fitness
        // Hard conflicts are weighted heavily (multiplied by 1000). Soft conflicts are weighted lightly.
        $totalPenalty = ($hardConflicts * 1000) + ($softConflicts * 10);
        $fitness = 1.0 / (1.0 + $totalPenalty);

        return [
            'fitness' => $fitness,
            'conflicts' => $hardConflicts,
        ];
    }

    /**
     * Tournament Selection.
     */
    private function tournamentSelect(array $evaluatedPop): array
    {
        $tournamentSize = 5;
        $best = null;

        for ($i = 0; $i < $tournamentSize; $i++) {
            $ind = $evaluatedPop[array_rand($evaluatedPop)];
            if ($best === null || $ind['fitness'] > $best['fitness']) {
                $best = $ind;
            }
        }

        return $best['chromosome'];
    }

    /**
     * Crossover: Uniform Crossover.
     */
    private function crossover(array $parent1, array $parent2): array
    {
        $child1 = [];
        $child2 = [];

        foreach ($this->sessions as $session) {
            $idx = $session['session_index'];
            if (mt_rand() / mt_getrandmax() < 0.5) {
                $child1[$idx] = $parent1[$idx];
                $child2[$idx] = $parent2[$idx];
            } else {
                $child1[$idx] = $parent2[$idx];
                $child2[$idx] = $parent1[$idx];
            }
        }

        return [$child1, $child2];
    }

    /**
     * Mutation.
     * CSP Heuristic: 40% chance of doing smart mutation to fix conflicts locally.
     */
    private function mutate(array $chromosome): array
    {
        foreach ($this->sessions as $session) {
            $idx = $session['session_index'];

            if (mt_rand() / mt_getrandmax() < $this->mutationRate) {
                $validRooms = $this->validRoomsPerSubjectType[$session['subject_type']] ?? array_keys($this->rooms);
                if (empty($validRooms)) {
                    $validRooms = array_keys($this->rooms);
                }

                // Smart Mutation (CSP-like local optimization)
                if (mt_rand() / mt_getrandmax() < 0.40) {
                    // Try to find a conflict-free spot for this single session
                    $placed = false;
                    for ($attempt = 0; $attempt < 10; $attempt++) {
                        $day = mt_rand(1, $this->maxDays);
                        $validStartsForDuration = $this->validStarts[$session['duration']] ?? [1];
                        $startPeriod = $validStartsForDuration[array_rand($validStartsForDuration)];
                        $roomId = $validRooms[array_rand($validRooms)];

                        // Check if any period overlaps with a break period
                        $clash = false;
                        for ($offset = 0; $offset < $session['duration']; $offset++) {
                            if (in_array($startPeriod + $offset, $this->breakPeriods)) {
                                $clash = true;
                                break;
                            }
                        }

                        if ($clash) continue;

                        // Evaluate clash against other genes in this chromosome (excluding current gene)
                        foreach ($this->sessions as $otherSession) {
                            if ($otherSession['session_index'] === $idx) continue;

                            $otherGene = $chromosome[$otherSession['session_index']];
                            if ($otherGene['day'] !== $day) continue;

                            // Check time overlaps
                            $start1 = $startPeriod;
                            $end1 = $startPeriod + $session['duration'] - 1;
                            $start2 = $otherGene['start_period'];
                            $end2 = $otherGene['start_period'] + $otherSession['duration'] - 1;

                            if ($start1 <= $end2 && $start2 <= $end1) {
                                // Overlaps in time, check clashes
                                if ($otherSession['teacher_id'] === $session['teacher_id']) {
                                    $clash = true;
                                    break;
                                }
                                if ($otherSession['rombel_id'] === $session['rombel_id']) {
                                    $clash = true;
                                    break;
                                }
                                if ($otherGene['room_id'] === $roomId) {
                                    $clash = true;
                                    break;
                                }
                            }
                        }

                        if (!$clash) {
                            $chromosome[$idx] = [
                                'day' => $day,
                                'start_period' => $startPeriod,
                                'room_id' => $roomId,
                            ];
                            $placed = true;
                            break;
                        }
                    }

                    // If smart placement fails, fallback to standard random mutation
                    if (!$placed) {
                        $day = mt_rand(1, $this->maxDays);
                        $validStartsForDuration = $this->validStarts[$session['duration']] ?? [1];
                        $startPeriod = $validStartsForDuration[array_rand($validStartsForDuration)];
                        $roomId = $validRooms[array_rand($validRooms)];

                        $chromosome[$idx] = [
                            'day' => $day,
                            'start_period' => $startPeriod,
                            'room_id' => $roomId,
                        ];
                    }
                } else {
                    // Standard Random Mutation
                    $day = mt_rand(1, $this->maxDays);
                    $validStartsForDuration = $this->validStarts[$session['duration']] ?? [1];
                    $startPeriod = $validStartsForDuration[array_rand($validStartsForDuration)];
                    $roomId = $validRooms[array_rand($validRooms)];

                    $chromosome[$idx] = [
                        'day' => $day,
                        'start_period' => $startPeriod,
                        'room_id' => $roomId,
                    ];
                }
            }
        }

        return $chromosome;
    }

    /**
     * Save the generated chromosome to schedules table.
     */
    private function saveSchedule(array $chromosome)
    {
        // Delete existing draft schedules for this academic year
        Schedule::where('academic_year_id', $this->academicYearId)
            ->where('status', 'draft')
            ->delete();

        $schedulesToInsert = [];
        foreach ($this->sessions as $session) {
            $gene = $chromosome[$session['session_index']];

            $schedulesToInsert[] = [
                'academic_year_id' => $this->academicYearId,
                'rombel_id' => $session['rombel_id'],
                'subject_id' => $session['subject_id'],
                'teacher_id' => $session['teacher_id'],
                'room_id' => $gene['room_id'],
                'day_of_week' => $gene['day'],
                'start_period' => $gene['start_period'],
                'end_period' => $gene['start_period'] + $session['duration'] - 1,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Batch insert for performance
        Schedule::insert($schedulesToInsert);
    }
}
