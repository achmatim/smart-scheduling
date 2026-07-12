<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    protected $fillable = [
        'period_number',
        'start_time',
        'end_time',
        'is_break',
    ];

    protected $casts = [
        'is_break' => 'boolean',
    ];
}
