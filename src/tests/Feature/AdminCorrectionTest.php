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

    /** 1. 承認待ちの修正申請がすべて表示される */
    public function test_admin_can_view_all_pending_requests()
    {
        // 承認待ち(pending)の申請を作成
        Correction::factory()->create([
            'user_id' => $this->staff->id,
            'status' => 'pending', // ステータス名はDB設計に合わせてください
            'reason' => '打刻忘れのため',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/stamp_correction_request/list'); // 申請一覧URL

        $response->assertStatus(200);
        $response->assertSee('pending'); // または「承認待ち」という文言
        $response->assertSee($this->staff->name);
    }

    /** 2. 承認済みの修正申請がすべて表示される */
    public function test_admin_can_view_all_approved_requests()
    {
        // 承認済み(approved)の申請を作成
        Correction::factory()->create([
            'user_id' => $this->staff->id,
            'status' => 'approved',
            'reason' => '修正完了',
        ]);

        // タブ切り替えなどでURLが分かれている場合（例: ?tab=approved）
        $response = $this->actingAs($this->admin)
            ->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);
        $response->assertSee('approved');
        $response->assertSee($this->staff->name);
    }

    /** 3. 修正申請の詳細内容が正しく表示される */
    public function test_admin_can_view_request_detail()
    {
        // 1. 紐づく勤怠データも一応作成しておく
        $attendance = Attendance::factory()->create(['date' => '2026-03-07']);

        // 2. requested_data を配列で定義して作成
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

        // 詳細画面へのアクセス
        $response = $this->actingAs($this->admin)
            ->get("/stamp_correction_request/approve/{$request->id}");

        // 検証
        $response->assertStatus(200);
        $response->assertSee('電車遅延のため');
        $response->assertSee($this->staff->name);
        $response->assertSee('09:30'); // 申請された時刻が表示されているか
    }
/** 4. 修正申請の承認処理が正しく行われる */
    public function test_admin_can_approve_request()
    {
        // 関連する勤怠データも作成
        $attendance = Attendance::factory()->create(['date' => '2026-03-07']);

        $request = Correction::factory()->create([
            'user_id' => $this->staff->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            // コントローラーが期待する配列構造を渡す
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

        // back()のリダイレクト先を確認（セッションにsuccessがあるか等）
        $response->assertStatus(302);
        $this->assertDatabaseHas('corrections', [ // テーブル名は適宜
            'id' => $request->id,
            'status' => 'approved',
        ]);
    }
}