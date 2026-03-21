<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // テスト中の「現在時刻」を固定する（テストの安定性のため）
        Carbon::setTestNow(Carbon::parse('2026-03-21 10:00:00'));
    }

    /**
     * 現在の日時情報が画面に表示されているか
     */
    public function test_current_datetime_is_displayed()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/attendance');

        // 1. 日付のチェック（Bladeの表示に合わせて「m」を「n」に、「d」を「j」に変える）
        // 形式: 2026年3月21日
        $expectedDate = Carbon::now()->format('Y年n月j日');
        $response->assertSee($expectedDate);

        // 2. 時刻のチェック
        // JavaScriptで上書きされる前の「初期値」を確認するか、
        // もしPHP側で初期値をセットしているならその値を確認します。
        // 今回は表示の有無だけ確認できればOKであれば、以下のようにid属性の存在を確認するのも手です。
        $response->assertSee('id="current-time"', false);
    }
    /**
     * 勤務外（データなし）の場合、ステータスが「勤務外」となる
     */
    public function test_status_is_outside_working_hours_when_no_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('勤務外');
    }

    /**
     * 出勤中の場合、ステータスが「出勤中」となる
     */
    public function test_status_is_working_when_checked_in()
    {
        $user = User::factory()->create();
        // 出勤データのみ作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'check_in' => Carbon::now()->subHours(2),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
    }

    /**
     * 休憩中の場合、ステータスが「休憩中」となる
     */
    public function test_status_is_on_break_when_break_started()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'check_in' => Carbon::now()->subHours(3),
        ]);
        // 休憩開始データ（終了時間がないもの）を作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::now()->subMinutes(30),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }

    /**
     * 退勤済の場合、ステータスが「退勤済」となる
     */
    public function test_status_is_finished_when_checked_out()
    {
        $user = User::factory()->create();
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'check_in' => Carbon::now()->subHours(8),
            'check_out' => Carbon::now()->subMinutes(5),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
    }
}