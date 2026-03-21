<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class BreakTimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // 各テストの前提として「出勤済み」の状態を作っておく
        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->format('Y-m-d'), // ← これを追加
            'check_in' => Carbon::now()->subHours(4),
        ]);
    }

    /**
     * 1. 休憩ボタンが正しく機能する
     */
    public function test_break_start_button_functions_correctly()
    {
        $response = $this->post(route('user.attendance.punch'), ['type' => 'break_start']);
        
        $response->assertStatus(302); // 処理後リダイレクトされるか
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $this->attendance->id,
            'end_time' => null, // 開始直後は終了時刻が空であること
        ]);
    }

    /**
     * 2. 休憩は１日に何回もできる
     */
    public function test_multiple_break_starts_are_allowed()
    {
        // 3回休憩を繰り返す
        for ($i = 1; $i <= 3; $i++) {
            $this->post(route('user.attendance.punch'), ['type' => 'break_start']);
            $this->post(route('user.attendance.punch'), ['type' => 'break_end']);
        }

        $this->assertEquals(3, $this->attendance->breakTimes()->count());
    }

    /**
     * 3. 休憩戻ボタンが正しく機能する
     */
    public function test_break_end_button_functions_correctly()
    {
        // まず休憩を開始させる
        $break = $this->attendance->breakTimes()->create(['start_time' => Carbon::now()]);

        $response = $this->post(route('user.attendance.punch'), ['type' => 'break_end']);

        $response->assertStatus(302);
        // データベースの end_time が更新されているか
        $this->assertNotNull($break->refresh()->end_time);
    }

    /**
     * 4. 休憩戻は１日に何回もできる
     */
    public function test_multiple_break_ends_are_allowed()
    {
        // 複数回の「開始→終了」がすべて正常ステータスで終わるか
        for ($i = 1; $i <= 3; $i++) {
            $this->post(route('user.attendance.punch'), ['type' => 'break_start'])->assertStatus(302);
            $response = $this->post(route('user.attendance.punch'), ['type' => 'break_end']);
            $response->assertStatus(302);
        }
        
        // すべての休憩データに終了時刻が入っているか
        $unfinishedBreaks = $this->attendance->breakTimes()->whereNull('end_time')->count();
        $this->assertEquals(0, $unfinishedBreaks);
    }

    /**
     * 5. 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_break_times_are_visible_on_attendance_list()
    {
        // テスト用の休憩データを作成
        $startTime = Carbon::now()->setTime(12, 0, 0);
        $endTime = Carbon::now()->setTime(13, 0, 0);
        
        $this->attendance->breakTimes()->create([
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('1:00'); // 休憩時間が「1:00」として表示されているか確認
    }
}