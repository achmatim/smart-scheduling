<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAvailability extends Model
{
    use \App\Traits\BelongsToSchool;

    protected $fillable = [
        'teacher_id',
        'day_of_week',
        'period_number',
        'is_available',
        'school_id',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
