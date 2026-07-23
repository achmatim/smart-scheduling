<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use \App\Traits\BelongsToSchool;

    protected $fillable = [
        'period_number',
        'start_time',
        'end_time',
        'is_break',
        'school_id',
    ];

    protected $casts = [
        'is_break' => 'boolean',
    ];
}
