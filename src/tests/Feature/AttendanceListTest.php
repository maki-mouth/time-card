<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_see_own_attendance_list()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-03-01',
            'check_in' => '12:08:00',
        ]);

        $otherUser = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2026-03-02',
            'check_in' => '09:00:00',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertSee('12:08');
        $response->assertDontSee('09:00');
    }

    public function test_current_month_is_displayed_by_default()
    {
        Carbon::setTestNow(Carbon::parse('2026-03-15'));

        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026/03');
    }

    public function test_can_navigate_to_previous_month()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-02-10',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/list?month=2026-02');

        $response->assertStatus(200);
        $response->assertSee('2026/02');
        $response->assertSee('02/10');
    }

    public function test_can_navigate_to_next_month()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-04-05',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/list?month=2026-04');

        $response->assertStatus(200);
        $response->assertSee('2026/04');
        $response->assertSee('04/05');
    }

    public function test_can_navigate_to_attendance_detail()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-03-21',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertSee(route('user.attendance.show', ['id' => $attendance->id]));

        $detailResponse = $this->actingAs($this->user)->get(route('user.attendance.show', ['id' => $attendance->id]));
        $detailResponse->assertStatus(200);
    }
}