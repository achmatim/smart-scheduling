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
     * Flag to prevent infinite recursion during Auth check queries.
     *
     * @var bool
     */
    private static bool $resolving = false;

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

        if (self::$resolving) {
            return null;
        }

        self::$resolving = true;

        try {
            if (Auth::check() && Auth::user()->school_id !== null) {
                $id = (int) Auth::user()->school_id;
                self::$resolving = false;
                return $id;
            }
        } catch (\Throwable $e) {
            // Prevent crash in case DB/Auth is not ready
        }

        self::$resolving = false;
        return null;
    }
}
