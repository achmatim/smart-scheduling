<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Rombel;
use App\Models\Subject;
use App\Models\Room;
use App\Models\Period;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleReportTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $ay;
    private $teacher;
    private $rombel;
    private $subject;
    private $room;
    private $period;
    private $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->ay = AcademicYear::create([
            'year' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
            'is_locked' => false,
        ]);

        $this->teacher = Teacher::create([
            'nip' => '12345',
            'name' => 'Budi Santoso',
            'email' => 'budi@test.com',
        ]);

        $this->rombel = Rombel::create([
            'name' => 'VII A',
            'grade' => 7,
        ]);

        $this->subject = Subject::create([
            'code' => 'MTK',
            'name' => 'Matematika',
            'type' => 'umum',
        ]);

        $this->room = Room::create([
            'code' => 'R101',
            'name' => 'Kelas VII A',
            'type' => 'umum',
        ]);

        $this->period = Period::create([
            'period_number' => 1,
            'start_time' => '07:15',
            'end_time' => '07:55',
            'is_break' => false,
        ]);

        $this->schedule = Schedule::create([
            'academic_year_id' => $this->ay->id,
            'rombel_id' => $this->rombel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'day_of_week' => 1,
            'start_period' => 1,
            'end_period' => 1,
            'status' => 'draft',
        ]);
    }

    public function test_report_routes_redirect_guests()
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->get(route('reports.print'))->assertRedirect(route('login'));
        $this->get(route('reports.excel'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_reports_index()
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.index', [
                'academic_year_id' => $this->ay->id,
                'sort_by' => 'teacher',
            ]));

        $response->assertStatus(200);
        $response->assertSeeText('Laporan Jadwal Pelajaran');
        $response->assertSeeText('Budi Santoso');
        $response->assertSeeText('VII A');
        $response->assertSeeText('Matematika');
    }

    public function test_authenticated_user_can_access_print_view()
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.print', [
                'academic_year_id' => $this->ay->id,
                'sort_by' => 'rombel',
            ]));

        $response->assertStatus(200);
        $response->assertSeeText('Laporan Jadwal Pelajaran');
        $response->assertSeeText('SMP Manggala');
        $response->assertSeeText('Budi Santoso');
        $response->assertSee('window.print()');
    }

    public function test_authenticated_user_can_export_report_excel()
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.excel', [
                'academic_year_id' => $this->ay->id,
                'sort_by' => 'subject',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel');
        $response->assertHeader('Content-Disposition', 'attachment; filename="laporan_jadwal_2026_2027_Ganjil.xls"');
        $response->assertSeeText('LAPORAN JADWAL PELAJARAN SMP MANGGALA');
        $response->assertSeeText('Budi Santoso');
    }
}
