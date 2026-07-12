<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RombelController;
use App\Http\Controllers\TeacherAvailabilityController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Authentication Routes (Guest Access)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Logout Route (Auth Access)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Dashboard & School Management Routes
Route::middleware('auth')->group(function () {
    
    // Redirect home to dashboard
    Route::redirect('/', '/dashboard');

    // Dashboard & Academic Years
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/academic-years', [DashboardController::class, 'storeYear'])->name('academic-years.store');
    Route::post('/academic-years/{id}/activate', [DashboardController::class, 'activateYear'])->name('academic-years.activate');

    // Master Data CRUD Resources
    Route::resource('teachers', TeacherController::class);
    Route::resource('subjects', SubjectController::class);
    Route::resource('rooms', RoomController::class);
    Route::resource('rombels', RombelController::class);
    Route::resource('periods', PeriodController::class); // Master Jam CRUD

    // Teacher Availabilities
    Route::get('/availabilities', [TeacherAvailabilityController::class, 'index'])->name('availabilities.index');
    Route::post('/availabilities/{teacher}', [TeacherAvailabilityController::class, 'update'])->name('availabilities.update');

    // Lesson Allocations
    Route::resource('lessons', LessonController::class);

    // Reports Menu Routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/print', [ReportController::class, 'print'])->name('reports.print');
    Route::get('/reports/excel', [ReportController::class, 'excel'])->name('reports.excel');
    // Schedule Generation & Viewer
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/export', [ScheduleController::class, 'exportExcel'])->name('schedules.export');
    Route::post('/schedules/generate', [ScheduleController::class, 'generate'])->name('schedules.generate');
    Route::get('/schedules/progress/{job}', [ScheduleController::class, 'checkProgress'])->name('schedules.progress');
    Route::post('/schedules/lock', [ScheduleController::class, 'lock'])->name('schedules.lock');
    Route::post('/schedules/unlock', [ScheduleController::class, 'unlock'])->name('schedules.unlock');
});
