<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class TenantManager
{
    /**
     * The active school ID in the current execution context.
     * Useful for background queue jobs or console seeding commands.
     *
     * @var int|null
     */
    private static ?int $schoolId = null;

    /**
     * Explicitly set the active school ID.
     */
    public static function setSchoolId(?int $schoolId): void
    {
        self::$schoolId = $schoolId;
    }

    /**
     * Retrieve the active school ID.
     * Resolves in order:
     * 1. Manually set school ID (e.g. from background queue job)
     * 2. Logged-in user's school ID
     */
    public static function getSchoolId(): ?int
    {
        if (self::$schoolId !== null) {
            return self::$schoolId;
        }

        if (Auth::check() && Auth::user()->school_id !== null) {
            return (int) Auth::user()->school_id;
        }

        return null;
    }
}
