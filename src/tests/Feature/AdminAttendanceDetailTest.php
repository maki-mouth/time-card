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
        
        // 管理者ユーザー（メール認証済み）
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // テスト対象となるスタッフの勤怠データ
        $this->staffAttendance = Attendance::factory()->create();
    }

    /**
     * 勤怠詳細画面に表示されるデータが選択されたものになっている
     */
    public function test_admin_can_see_specific_attendance_detail()
    {
        // 名前を明示的に指定してデータを作成
        $user = User::factory()->create(['name' => '若松 充']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-03-21'
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);

        // 1. 名前が表示されているか
        $response->assertSee('若松 充');

        // 2. 日付が日本語形式（HTMLの出力）で表示されているか確認
        // HTMLソースに合わせて「2026年」と「3月21日」に分けてチェックすると確実です
        $response->assertSee('2026年');
        $response->assertSee('3月21日');
    }
    /**
     * 出勤時間が退勤時間より後になっている場合
     */
    public function test_admin_validation_check_in_after_check_out()
    {

        // GETで表示しているURLと同じ場所にPOSTしてみる
        // (web.phpでの定義に合わせて "/admin/attendance/edit/{id}" か "/admin/attendance/{id}")
        $url = "/admin/attendance/edit/{$this->staffAttendance->id}"; 

        $response = $this->actingAs($this->admin)->post($url, [
            'check_in' => '18:00',
            'check_out' => '09:00',
            'reason' => '管理者による修正',
        ]);

        $response->assertSessionHasErrors([], '休憩時間が不適切な値です');
    }
    /**
     * 休憩開始時間が退勤時間より後になっている場合、バリデーションメッセージが表示される
     */
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

    /**
     * 休憩終了時間が退勤時間より後になっている場合、バリデーションメッセージが表示される
     */
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

    /**
     * 備考欄が未入力の場合、バリデーションメッセージが表示される
     */
    public function test_admin_validation_reason_is_required()
    {
        $response = $this->actingAs($this->admin)->post("/admin/attendance/edit/{$this->staffAttendance->id}", [
            'check_in' => '09:00',
            'check_out' => '18:00',
            'reason' => '', // 未入力
        ]);

        $response->assertSessionHasErrors(['reason']);
    }
}