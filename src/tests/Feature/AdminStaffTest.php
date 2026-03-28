<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Tests\TestCase;
use Carbon\Carbon;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->staff = User::factory()->create(['role' => 'user']);
    }

    public function test_admin_can_view_user_profile_info()
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/staff/list");

        $response->assertStatus(200);
        $response->assertSee($this->staff->name);
        $response->assertSee($this->staff->email);
    }

    public function test_admin_can_view_correct_attendance_data()
    {
        Attendance::factory()->create([
            'user_id' => $this->staff->id,
            'date' => '2026-03-21',
            'check_in' => '2026-03-21 09:00:00',
            'check_out' => '2026-03-21 18:00:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/attendance/staff/{$this->staff->id}?month=2026-03");

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_admin_can_navigate_to_previous_month()
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/attendance/staff/{$this->staff->id}?month=2026-02");

        $response->assertStatus(200);
        $response->assertSee('2026/02');
    }

    public function test_admin_can_navigate_to_next_month()
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/attendance/staff/{$this->staff->id}?month=2026-04");

        $response->assertStatus(200);
        $response->assertSee('2026/04');
    }

    public function test_admin_can_click_detail_and_navigate()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->staff->id,
            'date' => '2026-03-21',
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/attendance/staff/{$this->staff->id}");

        $detailUrl = route('admin.attendance.show', ['id' => $attendance->id]);

        $response->assertSee($detailUrl);

        $this->get($detailUrl)->assertStatus(200);
    }
}