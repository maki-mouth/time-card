<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-03-21 10:00:00'));
    }

    public function test_current_datetime_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $expectedDate = Carbon::now()->format('Y年n月j日');
        $response->assertSee($expectedDate);

        $response->assertSee('id="current-time"', false);
    }

    public function test_status_is_outside_working_hours_when_no_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('勤務外');
    }

    public function test_status_is_working_when_checked_in()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'check_in' => Carbon::now()->subHours(2),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
    }

    public function test_status_is_on_break_when_break_started()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'check_in' => Carbon::now()->subHours(3),
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::now()->subMinutes(30),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }

    public function test_status_is_finished_when_checked_out()
    {
        $user = User::factory()->create();
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'check_in' => Carbon::now()->subHours(8),
            'check_out' => Carbon::now()->subMinutes(5),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
    }
}