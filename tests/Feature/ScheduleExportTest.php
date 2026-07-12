<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use App\Models\Rombel;
use App\Models\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_route_redirects_unauthenticated_guests_to_login()
    {
        $response = $this->get(route('schedules.export'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_export_excel()
    {
        // 1. Seed necessary records
        $user = User::factory()->create();
        
        $ay = AcademicYear::create([
            'year' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
            'is_locked' => false,
        ]);

        $rombel = Rombel::create([
            'name' => 'VII A',
            'grade' => 7,
        ]);

        Period::create([
            'period_number' => 1,
            'start_time' => '07:15',
            'end_time' => '07:55',
            'is_break' => false,
        ]);

        // 2. Perform authenticated request
        $response = $this->actingAs($user)
            ->get(route('schedules.export', [
                'filter_type' => 'rombel',
                'filter_id' => $rombel->id,
            ]));

        // 3. Verify response
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel');
        $response->assertHeader('Content-Disposition', 'attachment; filename="jadwal_rombel_Kelas_VII_A.xls"');
        $response->assertSeeText('JADWAL PELAJARAN SMP MANGGALA');
        $response->assertSeeText('Kelas VII A');
    }
}
