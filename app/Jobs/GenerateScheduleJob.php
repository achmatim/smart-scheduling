<?php

namespace App\Jobs;

use App\Models\SchedulingJob;
use App\Services\SchedulingEngine;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $academicYearId;
    private $schedulingJobId;
    private $config;

    /**
     * Create a new job instance.
     */
    public function __construct(int $academicYearId, int $schedulingJobId, array $config = [])
    {
        $this->academicYearId = $academicYearId;
        $this->schedulingJobId = $schedulingJobId;
        $this->config = $config;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobModel = SchedulingJob::find($this->schedulingJobId);
        if (!$jobModel) {
            Log::error("SchedulingJob with ID {$this->schedulingJobId} not found.");
            return;
        }

        try {
            // Sensible GA settings
            $popSize = $this->config['pop_size'] ?? 100;
            $maxGenerations = $this->config['max_generations'] ?? 120;
            
            $engine = new SchedulingEngine($this->academicYearId, [
                'pop_size' => $popSize,
                'max_generations' => $maxGenerations,
                'crossover_rate' => 0.8,
                'mutation_rate' => 0.15,
                'elitism_count' => max(2, (int)($popSize * 0.05)),
            ]);

            $result = $engine->run($jobModel);
            
            Log::info("SchedulingJob Completed. Fitness: {$result['fitness']}, Conflicts: {$result['conflicts']}");
        } catch (Exception $e) {
            Log::error("Error during schedule generation: " . $e->getMessage());
            
            $jobModel->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
