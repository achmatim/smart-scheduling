<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use \App\Traits\BelongsToSchool;

    protected $fillable = [
        'code',
        'name',
        'type',
        'capacity',
        'school_id',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
