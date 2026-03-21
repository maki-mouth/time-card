<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase; // ← クラス内でこれが必要

    protected $user;
    protected $attendance;
    protected $targetDate = '2026-03-21';

    protected function setUp(): void
    {
        parent::setUp();

        // テストユーザーの作成とログイン
        $this->user = User::factory()->create(['name' => 'テスト太郎']);

        // 勤怠データの作成（出勤 09:00 / 退勤 18:00）
        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $this->targetDate,
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
        ]);

        // 休憩データの作成（12:00 〜 13:00）
        BreakTime::factory()->create([
            'attendance_id' => $this->attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);
    }

    /**
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_attendance_detail_shows_correct_user_name()
    {
        $response = $this->actingAs($this->user)->get("/attendance/detail/{$this->attendance->id}");

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
    }

    /**
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_attendance_detail_shows_correct_date()
    {
        $response = $this->actingAs($this->user)->get("/attendance/detail/{$this->attendance->id}");

        $year = Carbon::parse($this->targetDate)->format('Y年'); // 2026年
        $day  = Carbon::parse($this->targetDate)->format('n月j日'); // 3月21日

        $response->assertSee($year);
        $response->assertSee($day);
    }

    /**
     * 「出勤・退勤」にて記載されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_correct_punch_times()
    {
        $response = $this->actingAs($this->user)->get("/attendance/detail/{$this->attendance->id}");

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「休憩」にて記載されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_correct_rest_times()
    {
        $response = $this->actingAs($this->user)->get("/attendance/detail/{$this->attendance->id}");

        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}