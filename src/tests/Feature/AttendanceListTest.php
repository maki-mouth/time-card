<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * 自分が行った勤怠情報がすべて表示される
     */
    public function test_user_can_see_own_attendance_list()
    {
        // 自分の勤怠データ：12:08に出勤したことにする
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-03-01',
            'check_in' => '12:08:00', // 具体的な時間を指定
        ]);

        // 他人のデータ：09:00に出勤したことにする（表示されてはいけない）
        $otherUser = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2026-03-02',
            'check_in' => '09:00:00', // 自分の画面には出ないはずの時間
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertStatus(200);
        
        // 日付はカレンダーとして常に出ている可能性があるので、
        // 「打刻された時間」が表示されているか／いないかで検証します
        $response->assertSee('12:08');     // 自分の打刻時間は見える
        $response->assertDontSee('09:00'); // 他人の打刻時間は見えない
    }
    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_current_month_is_displayed_by_default()
    {
        // 現在時刻を固定（例：2026年3月）
        Carbon::setTestNow(Carbon::parse('2026-03-15'));

        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026/03'); // 画面上の月表示を確認
    }

    /**
     * 「前月」を押したときに表示月の前月の情報が表示される
     */
    public function test_can_navigate_to_previous_month()
    {
        // 2月のデータを作成
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-02-10',
        ]);

        // クエリパラメータで2月を指定してリクエスト（「前月」リンクの動作シミュレート）
        $response = $this->actingAs($this->user)->get('/attendance/list?month=2026-02');

        $response->assertStatus(200);
        $response->assertSee('2026/02');
        $response->assertSee('02/10');
    }

    /**
     * 「翌月」を押したときに表示月の翌月の情報が表示される
     */
    public function test_can_navigate_to_next_month()
    {
        // 4月のデータを作成
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-04-05',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/list?month=2026-04');

        $response->assertStatus(200);
        $response->assertSee('2026/04');
        $response->assertSee('04/05');
    }

    /**
     * 「詳細」を押すとその日の勤怠詳細画面に遷移する
     */
    public function test_can_navigate_to_attendance_detail()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-03-21',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/list');

        // 詳細リンクの遷移先URLが含まれているか確認
        // HTML構造に合わせて route('attendance.detail', ['id' => ...]) などに調整してください
        $response->assertSee(route('user.attendance.show', ['id' => $attendance->id]));
        
        // 実際にそのURLにアクセスして200が返るかまで見るとより正確です
        $detailResponse = $this->actingAs($this->user)->get(route('user.attendance.show', ['id' => $attendance->id]));
        $detailResponse->assertStatus(200);
    }
}