<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $attendance;
    protected $targetDate = '2026-03-21';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'テスト太郎']);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $this->targetDate,
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $this->attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);
    }

    public function test_attendance_detail_shows_correct_user_name()
    {
        $response = $this->actingAs($this->user)->get("/attendance/detail/{$this->attendance->id}");

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
    }

    public function test_attendance_detail_shows_correct_date()
    {
        $response = $this->actingAs($this->user)->get("/attendance/detail/{$this->attendance->id}");

        $year = Carbon::parse($this->targetDate)->format('Y年');
        $day  = Carbon::parse($this->targetDate)->format('n月j日');

        $response->assertSee($year);
        $response->assertSee($day);
    }

    public function test_attendance_detail_shows_correct_punch_times()
    {
        $response = $this->actingAs($this->user)->get("/attendance/detail/{$this->attendance->id}");

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_attendance_detail_shows_correct_rest_times()
    {
        $response = $this->actingAs($this->user)->get("/attendance/detail/{$this->attendance->id}");

        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}