<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Correction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->attendance = Attendance::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_check_in_time_cannot_be_after_check_out_time()
    {
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($verifiedUser)->post("/attendance/detail/{$this->attendance->id}", [
            'check_in' => '18:00',
            'check_out' => '09:00',
            'reason' => '修正理由の入力',
        ]);

        $response->assertSessionHasErrors(['check_out']);
    }

    public function test_break_start_time_cannot_be_after_check_out_time()
    {
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($verifiedUser)->post("/attendance/detail/{$this->attendance->id}", [
            'check_in' => '09:00',
            'check_out' => '18:00',
            'breaks' => [
                ['start' => '19:00', 'end' => '20:00'] // before:check_out などに反する
            ],
            'reason' => '修正理由の入力',
        ]);

        $response->assertSessionHasErrors([], '休憩時間が不適切な値です');
    }

    public function test_break_end_time_cannot_be_after_check_out_time()
    {
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($verifiedUser)->post("/attendance/detail/{$this->attendance->id}", [
            'check_in' => '09:00',
            'check_out' => '18:00',
            'breaks' => [
                ['start' => '12:00', 'end' => '19:00']
            ],
            'reason' => '修正理由の入力',
        ]);

        $response->assertSessionHasErrors([], '休憩時間が不適切な値です');
    }

    public function test_reason_is_required_for_correction_request()
    {
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now() 
        ]);

        $response = $this->actingAs($verifiedUser)->post("/attendance/detail/{$this->attendance->id}", [
            'check_in' => '09:00',
            'check_out' => '18:00',
            'reason' => '',
        ]);

        $response->assertSessionHasErrors(['reason']);
    }

    public function test_correction_request_can_be_submitted()
    {
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);

        $data = [
            'check_in' => '08:30',
            'check_out' => '17:30',
            'reason' => '電車遅延のため修正',
            'breaks' => [
            ['start' => '12:00', 'end' => '13:00']
            ]
        ];

        $response = $this->actingAs($verifiedUser)->post("/attendance/detail/{$this->attendance->id}", $data);

        $this->assertDatabaseHas('corrections', [
            'attendance_id' => $this->attendance->id,
            'user_id' => $verifiedUser->id,
            'reason' => '電車遅延のため修正',
            'status' => 'pending',
            'requested_data->check_in' => '08:30',
            'requested_data->check_out' => '17:30',
        ]);

        $response->assertStatus(302);
    }

    public function test_user_can_see_all_pending_requests()
    {
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);

        \App\Models\Correction::factory()->create([
            'user_id' => $verifiedUser->id,
            'status' => 'pending',
            'reason' => '承認待ちのテスト申請',
        ]);

        $response = $this->actingAs($verifiedUser)->get('/stamp_correction_request/list?tab=pending');

        $response->assertStatus(200);
        $response->assertSee('承認待ちのテスト申請');
    }

    public function test_user_can_see_all_approved_requests()
    {
        Correction::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'approved'
        ]);

        $response = $this->actingAs($this->user)->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);
        $response->assertSee('承認済み');
    }

    public function test_correction_request_detail_link_redirects_to_attendance_detail()
    {
        $correction = Correction::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance->id
        ]);

        $response = $this->actingAs($this->user)->get('/stamp_correction_request/list');

        $response->assertSee("/attendance/detail/{$this->attendance->id}");
    }
}