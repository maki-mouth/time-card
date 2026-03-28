<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendancePunchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-03-21 09:00:00'));
    }

    public function test_user_can_check_in()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/attendance', [
            'type' => 'check_in'
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => Carbon::now()->toDateTimeString(),
        ]);

        $response->assertStatus(302);
    }

    public function test_user_cannot_check_in_twice_a_day()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/attendance', ['type' => 'check_in']);

        Carbon::setTestNow(Carbon::now()->addMinutes(10));

        $this->actingAs($user)->post('/attendance', ['type' => 'check_in']);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'check_in' => '2026-03-21 09:00:00',
        ]);

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
            'check_in' => '2026-03-21 09:10:00',
        ]);
    }

    public function test_check_in_time_is_visible_on_attendance_list()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => Carbon::parse('2026-03-21 09:00:00'),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee('09:00');
    }

    public function test_user_can_check_out()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => Carbon::parse('2026-03-21 09:00:00'),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-21 18:00:00'));

        $response = $this->actingAs($user)->post('/attendance', [
            'type' => 'check_out'
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'check_out' => '2026-03-21 18:00:00',
        ]);

        $response->assertStatus(302);
    }

    public function test_check_out_time_is_visible_on_attendance_list()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => Carbon::parse('2026-03-21 09:00:00'),
            'check_out' => Carbon::parse('2026-03-21 18:00:00'),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee('18:00');
    }
}

