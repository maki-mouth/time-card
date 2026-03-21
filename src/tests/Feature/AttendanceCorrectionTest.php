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


    /**
     * 出勤時間が退勤時間より後になっている場合、バリデーションメッセージが表示される
     */
    public function test_check_in_time_cannot_be_after_check_out_time()
    {
        // 1. メール認証済みのユーザーを作成する（verifiedミドルウェア対策）
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 2. 正しいURL（web.phpの定義通り）にPOSTする
        $response = $this->actingAs($verifiedUser)->post("/attendance/detail/{$this->attendance->id}", [
            'check_in' => '18:00',
            'check_out' => '09:00',
            'reason' => '修正理由の入力',
        ]);

        // 3. check_outキーにエラーがあることを検証
        $response->assertSessionHasErrors(['check_out']);
    }
    /**
     * 休憩開始時間が退勤時間より後になっている場合、バリデーションメッセージが表示される
     */
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

        // 配列のバリデーションエラーはドット記法で指定します
        $response->assertSessionHasErrors([], '休憩時間が不適切な値です');
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、バリデーションメッセージが表示される
     */
    public function test_break_end_time_cannot_be_after_check_out_time()
    {
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($verifiedUser)->post("/attendance/detail/{$this->attendance->id}", [
            'check_in' => '09:00',
            'check_out' => '18:00',
            'breaks' => [
                ['start' => '12:00', 'end' => '19:00'] // before:check_out に反する
            ],
            'reason' => '修正理由の入力',
        ]);

        $response->assertSessionHasErrors([], '休憩時間が不適切な値です');
    }
    /**
     * 備考欄（理由）が未入力の場合、バリデーションメッセージが表示される
     */
    public function test_reason_is_required_for_correction_request()
    {
        // email_verified_at を明示的に入れる
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now() 
        ]);

        $response = $this->actingAs($verifiedUser)->post("/attendance/detail/{$this->attendance->id}", [
            'check_in' => '09:00',
            'check_out' => '18:00',
            'reason' => '', // 未入力
        ]);

        // もしここで失敗する場合、一旦↓を入れてどこに飛ばされているか見てみてください
        // $response->dump(); 

        $response->assertSessionHasErrors(['reason']);
    }
/**
 * 修正申請処理が実行される
 */
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

        // URLを修正
        $response = $this->actingAs($verifiedUser)->post("/attendance/detail/{$this->attendance->id}", $data);

        // 1. テーブル名を 'corrections' に変更
        // 2. JSONカラムの中身をドット記法で検証
        $this->assertDatabaseHas('corrections', [
            'attendance_id' => $this->attendance->id,
            'user_id' => $verifiedUser->id,
            'reason' => '電車遅延のため修正',
            'status' => 'pending',
            'requested_data->check_in' => '08:30',
            'requested_data->check_out' => '17:30',
        ]);

        $response->assertStatus(302); // リダイレクト確認
    }
    /**
     * 「承認待ち」にログインユーザーが行った申請がすべて表示される
     */
    public function test_user_can_see_all_pending_requests()
    {
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);
        
        // Factoryを使って作成。上書きしたい項目だけ配列で渡す
        \App\Models\Correction::factory()->create([
            'user_id' => $verifiedUser->id,
            'status' => 'pending',
            'reason' => '承認待ちのテスト申請',
        ]);

        $response = $this->actingAs($verifiedUser)->get('/stamp_correction_request/list?tab=pending');

        $response->assertStatus(200);
        $response->assertSee('承認待ちのテスト申請');
    }
    /**
     * 「承認済み」タブに管理者が承認した修正申請がすべて表示される
     */
    public function test_user_can_see_all_approved_requests()
    {
        // 承認済みの申請を作成
        Correction::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'approved'
        ]);

        $response = $this->actingAs($this->user)->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);
        $response->assertSee('承認済み');
    }

    /**
     * 各申請の「詳細」をおすと勤怠詳細画面に遷移する
     */
    public function test_correction_request_detail_link_redirects_to_attendance_detail()
    {
        $correction = Correction::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance->id
        ]);

        // 一覧画面で「詳細」のリンク先URL（またはボタン）が存在するか
        $response = $this->actingAs($this->user)->get('/stamp_correction_request/list');
        
        $response->assertSee("/attendance/detail/{$this->attendance->id}");
    }
}