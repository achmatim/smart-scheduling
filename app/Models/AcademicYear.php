<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use \App\Traits\BelongsToSchool;

    protected $fillable = [
        'year',
        'semester',
        'is_active',
        'is_locked',
        'school_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function schedulingJobs(): HasMany
    {
        return $this->hasMany(SchedulingJob::class);
    }
}
