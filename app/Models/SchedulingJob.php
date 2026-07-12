<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchedulingJob extends Model
{
    protected $fillable = [
        'academic_year_id',
        'status',
        'progress',
        'max_generations',
        'current_generation',
        'best_fitness',
        'conflicts',
        'error_message',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
