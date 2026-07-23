<?php

namespace App\Http\Controllers;

use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\TenantManager;

class PeriodController extends Controller
{
    /**
     * Display listing of periods.
     */
    public function index()
    {
        $periods = Period::orderBy('period_number')->get();
        return view('periods.index', compact('periods'));
    }

    /**
     * Store a new period.
     */
    public function store(Request $request)
    {
        $request->validate([
            'period_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('periods', 'period_number')->where('school_id', TenantManager::getSchoolId())
            ],
            'start_time' => 'required|string|regex:/^\d{2}:\d{2}$/', // e.g. "07:15"
            'end_time' => 'required|string|regex:/^\d{2}:\d{2}$/',
            'is_break' => 'boolean',
        ]);

        Period::create([
            'period_number' => $request->period_number,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_break' => $request->has('is_break'),
        ]);

        return redirect()->route('periods.index')->with('success', 'Jam Pelajaran (Sesi) berhasil ditambahkan.');
    }

    /**
     * Update an existing period.
     */
    public function update(Request $request, Period $period)
    {
        $request->validate([
            'period_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('periods', 'period_number')->ignore($period->id)->where('school_id', TenantManager::getSchoolId())
            ],
            'start_time' => 'required|string|regex:/^\d{2}:\d{2}$/',
            'end_time' => 'required|string|regex:/^\d{2}:\d{2}$/',
            'is_break' => 'boolean',
        ]);

        $period->update([
            'period_number' => $request->period_number,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_break' => $request->has('is_break'),
        ]);

        return redirect()->route('periods.index')->with('success', 'Jam Pelajaran (Sesi) berhasil diperbarui.');
    }

    /**
     * Delete a period.
     */
    public function destroy(Period $period)
    {
        $period->delete();
        return redirect()->route('periods.index')->with('success', 'Jam Pelajaran (Sesi) berhasil dihapus.');
    }
}
