<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Correction;
use Tests\TestCase;

class AdminCorrectionTest extends TestCase
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

    public function test_admin_can_view_all_pending_requests()
    {
        Correction::factory()->create([
            'user_id' => $this->staff->id,
            'status' => 'pending',
            'reason' => '打刻忘れのため',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('pending');
        $response->assertSee($this->staff->name);
    }

    public function test_admin_can_view_all_approved_requests()
    {
        Correction::factory()->create([
            'user_id' => $this->staff->id,
            'status' => 'approved',
            'reason' => '修正完了',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);
        $response->assertSee('approved');
        $response->assertSee($this->staff->name);
    }

    public function test_admin_can_view_request_detail()
    {
        $attendance = Attendance::factory()->create(['date' => '2026-03-07']);

        $request = Correction::factory()->create([
            'user_id' => $this->staff->id,
            'attendance_id' => $attendance->id,
            'reason' => '電車遅延のため',
            'status' => 'pending',
            'requested_data' => [
                'check_in' => '09:30',
                'check_out' => '18:30',
                'break_time' => [
                    ['start_time' => '12:00', 'end_time' => '13:00']
                ]
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/stamp_correction_request/approve/{$request->id}");

        $response->assertStatus(200);
        $response->assertSee('電車遅延のため');
        $response->assertSee($this->staff->name);
        $response->assertSee('09:30');
    }

    public function test_admin_can_approve_request()
    {
        $attendance = Attendance::factory()->create(['date' => '2026-03-07']);

        $request = Correction::factory()->create([
            'user_id' => $this->staff->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'requested_data' => [
                'check_in' => '09:00',
                'check_out' => '18:00',
                'break_time' => [
                    ['start_time' => '12:00', 'end_time' => '13:00']
                ]
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.request.approve', ['attendance_correct_request_id' => $request->id]));

        $response->assertStatus(302);
        $this->assertDatabaseHas('corrections', [
            'id' => $request->id,
            'status' => 'approved',
        ]);
    }
}