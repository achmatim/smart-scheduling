<?php

namespace App\Traits;

use App\Models\School;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSchool
{
    /**
     * Boot the trait to apply global scopes and lifecycle hooks.
     */
    protected static function bootBelongsToSchool(): void
    {
        // 1. Auto-populate school_id on creation
        static::creating(function ($model) {
            $schoolId = TenantManager::getSchoolId();
            if ($schoolId !== null && !$model->school_id) {
                $model->school_id = $schoolId;
            }
        });

        // 2. Auto-scope all queries to the active school
        static::addGlobalScope('school', function (Builder $builder) {
            $schoolId = TenantManager::getSchoolId();
            if ($schoolId !== null) {
                $builder->where($builder->getModel()->getTable() . '.school_id', $schoolId);
            }
        });
    }

    /**
     * Get the school that owns this model.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
