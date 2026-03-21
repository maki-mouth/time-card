<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendancePunchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // テスト時刻を固定（2026年3月21日 09:00:00）
        Carbon::setTestNow(Carbon::parse('2026-03-21 09:00:00'));
    }

    /**
     * 出勤ボタンが正しく機能する
     */
    public function test_user_can_check_in()
    {
        $user = User::factory()->create();

        // 出勤POSTリクエストを送信
        $response = $this->actingAs($user)->post('/attendance', [
            'type' => 'check_in'
        ]);

        // 1. DBに今日の出勤データが保存されているか確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => Carbon::now()->toDateTimeString(),
        ]);

        // 2. 打刻画面にリダイレクトされるか確認
        $response->assertStatus(302);
    }

    /**
     * 出勤は1日に1回のみである
     */
    public function test_user_cannot_check_in_twice_a_day()
    {
        $user = User::factory()->create();

        // 1回目の出勤（09:00）
        $this->actingAs($user)->post('/attendance', ['type' => 'check_in']);

        // 10分後に2回目の出勤を試みる
        Carbon::setTestNow(Carbon::now()->addMinutes(10));
        
        $this->actingAs($user)->post('/attendance', ['type' => 'check_in']);

        // DBを確認：2回目の時刻（09:10）で上書きされていないことを確認
        // つまり、最初の 09:00:00 のデータのみが存在するはず
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'check_in' => '2026-03-21 09:00:00',
        ]);
        
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
            'check_in' => '2026-03-21 09:10:00',
        ]);
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_check_in_time_is_visible_on_attendance_list()
    {
        $user = User::factory()->create();
        
        // 出勤データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => Carbon::parse('2026-03-21 09:00:00'),
        ]);

        // 勤怠一覧画面（/attendance/list）にアクセス
        // ※ルート名はあなたの設定に合わせて調整してください
        $response = $this->actingAs($user)->get('/attendance/list');

        // 画面に「09:00」が表示されているか確認
        $response->assertSee('09:00');
    }



        /**
     * 退勤ボタンが正しく機能する
     */
    public function test_user_can_check_out()
    {
        $user = User::factory()->create();
        
        // 1. まず出勤データを作成しておく（9:00に出勤した状態）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => Carbon::parse('2026-03-21 09:00:00'),
        ]);

        // 2. 18:00に時間を進めて退勤リクエストを送信
        Carbon::setTestNow(Carbon::parse('2026-03-21 18:00:00'));
        
        $response = $this->actingAs($user)->post('/attendance', [
            'type' => 'check_out'
        ]);

        // 3. DBの退勤時刻（check_out）が正しく更新されているか確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'check_out' => '2026-03-21 18:00:00',
        ]);

        $response->assertStatus(302);
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_check_out_time_is_visible_on_attendance_list()
    {
        $user = User::factory()->create();
        
        // 退勤済みのデータを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => Carbon::parse('2026-03-21 09:00:00'),
            'check_out' => Carbon::parse('2026-03-21 18:00:00'),
        ]);

        // 勤怠一覧画面（/attendance/list）にアクセス
        $response = $this->actingAs($user)->get('/attendance/list');

        // 画面に退勤時刻「18:00」が表示されているか確認
        $response->assertSee('18:00');
    }
}

