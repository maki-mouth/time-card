<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_see_all_users_attendance_of_the_day()
    {
        $today = Carbon::today()->format('Y-m-d');

        $userA = User::factory()->create(['name' => 'ユーザーA']);
        $userB = User::factory()->create(['name' => 'ユーザーB']);

        Attendance::factory()->create(['user_id' => $userA->id, 'date' => $today]);
        Attendance::factory()->create(['user_id' => $userB->id, 'date' => $today]);

        $response = $this->actingAs($this->admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('ユーザーA');
        $response->assertSee('ユーザーB');
    }

    public function test_admin_attendance_list_shows_current_date_initially()
    {
        $today = Carbon::today();

        $response = $this->actingAs($this->admin)->get('/admin/attendance/list');

        $response->assertSee($today->format('Y-m-d'));
    }

    public function test_admin_can_navigate_to_previous_day()
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $user = User::factory()->create(['name' => '昨日働いた人']);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $yesterday
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/attendance/list?date={$yesterday}");

        $response->assertStatus(200);
        $response->assertSee($yesterday);
        $response->assertSee('昨日働いた人');
    }

    public function test_admin_can_navigate_to_next_day()
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $user = User::factory()->create(['name' => '明日働く予定の人']);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $tomorrow
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/attendance/list?date={$tomorrow}");

        $response->assertStatus(200);
        $response->assertSee($tomorrow);
        $response->assertSee('明日働く予定の人');
    }
}