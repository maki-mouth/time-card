<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class BreakTimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->format('Y-m-d'),
            'check_in' => Carbon::now()->subHours(4),
        ]);
    }

    public function test_break_start_button_functions_correctly()
    {
        $response = $this->post(route('user.attendance.punch'), ['type' => 'break_start']);

        $response->assertStatus(302);
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $this->attendance->id,
            'end_time' => null,
        ]);
    }

    public function test_multiple_break_starts_are_allowed()
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->post(route('user.attendance.punch'), ['type' => 'break_start']);
            $this->post(route('user.attendance.punch'), ['type' => 'break_end']);
        }

        $this->assertEquals(3, $this->attendance->breakTimes()->count());
    }

    public function test_break_end_button_functions_correctly()
    {
        $break = $this->attendance->breakTimes()->create(['start_time' => Carbon::now()]);

        $response = $this->post(route('user.attendance.punch'), ['type' => 'break_end']);

        $response->assertStatus(302);
        $this->assertNotNull($break->refresh()->end_time);
    }

    public function test_multiple_break_ends_are_allowed()
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->post(route('user.attendance.punch'), ['type' => 'break_start'])->assertStatus(302);
            $response = $this->post(route('user.attendance.punch'), ['type' => 'break_end']);
            $response->assertStatus(302);
        }

        $unfinishedBreaks = $this->attendance->breakTimes()->whereNull('end_time')->count();
        $this->assertEquals(0, $unfinishedBreaks);
    }

    public function test_break_times_are_visible_on_attendance_list()
    {
        $startTime = Carbon::now()->setTime(12, 0, 0);
        $endTime = Carbon::now()->setTime(13, 0, 0);

        $this->attendance->breakTimes()->create([
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('1:00');
    }
}