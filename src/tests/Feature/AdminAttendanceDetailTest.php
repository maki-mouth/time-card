<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $staffAttendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->staffAttendance = Attendance::factory()->create();
    }

    public function test_admin_can_see_specific_attendance_detail()
    {
        $user = User::factory()->create(['name' => '若松 充']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-03-21'
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $response->assertSee('若松 充');

        $response->assertSee('2026年');
        $response->assertSee('3月21日');
    }

    public function test_admin_validation_check_in_after_check_out()
    {

        $url = "/admin/attendance/edit/{$this->staffAttendance->id}"; 

        $response = $this->actingAs($this->admin)->post($url, [
            'check_in' => '18:00',
            'check_out' => '09:00',
            'reason' => '管理者による修正',
        ]);

        $response->assertSessionHasErrors([], '休憩時間が不適切な値です');
    }

    public function test_admin_validation_break_start_after_check_out()
    {
        $response = $this->actingAs($this->admin)->post("/admin/attendance/edit/{$this->staffAttendance->id}", [
            'check_in' => '09:00',
            'check_out' => '18:00',
            'breaks' => [
                ['start' => '19:00', 'end' => '20:00']
            ],
            'reason' => '管理者による修正',
        ]);

        $response->assertSessionHasErrors([], '休憩時間が不適切な値です');
    }

    public function test_admin_validation_break_end_after_check_out()
    {
        $response = $this->actingAs($this->admin)->post("/admin/attendance/edit/{$this->staffAttendance->id}", [
            'check_in' => '09:00',
            'check_out' => '18:00',
            'breaks' => [
                ['start' => '12:00', 'end' => '19:00']
            ],
            'reason' => '管理者による修正',
        ]);

        $response->assertSessionHasErrors([], '休憩時間が不適切な値です');
    }

    public function test_admin_validation_reason_is_required()
    {
        $response = $this->actingAs($this->admin)->post("/admin/attendance/edit/{$this->staffAttendance->id}", [
            'check_in' => '09:00',
            'check_out' => '18:00',
            'reason' => '',
        ]);

        $response->assertSessionHasErrors(['reason']);
    }
}