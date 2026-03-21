<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // 管理者ユーザーを作成（roleなどで判別している想定）
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_admin_can_see_all_users_attendance_of_the_day()
    {
        $today = Carbon::today()->format('Y-m-d');
        
        // ユーザーAとBの今日の勤怠を作成
        $userA = User::factory()->create(['name' => 'ユーザーA']);
        $userB = User::factory()->create(['name' => 'ユーザーB']);
        
        Attendance::factory()->create(['user_id' => $userA->id, 'date' => $today]);
        Attendance::factory()->create(['user_id' => $userB->id, 'date' => $today]);

        // 管理者として一覧画面にアクセス
        $response = $this->actingAs($this->admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('ユーザーA');
        $response->assertSee('ユーザーB');
    }

    /**
     * 遷移した際に現在の日付が表示される
     */
    public function test_admin_attendance_list_shows_current_date_initially()
    {
        $today = Carbon::today();
        
        $response = $this->actingAs($this->admin)->get('/admin/attendance/list');

        // Bladeの表示形式に合わせて調整してください（例: 2026-03-21 または 2026年03月21日）
        $response->assertSee($today->format('Y-m-d'));
    }

    /**
     * 「前日」を押したときに前の日の勤怠情報が表示される
     */
    public function test_admin_can_navigate_to_previous_day()
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $user = User::factory()->create(['name' => '昨日働いた人']);
        
        // 昨日のデータを作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $yesterday
        ]);

        // クエリパラメータで日付を指定してアクセス（「前日」ボタンのリンク先を想定）
        $response = $this->actingAs($this->admin)->get("/admin/attendance/list?date={$yesterday}");

        $response->assertStatus(200);
        $response->assertSee($yesterday);
        $response->assertSee('昨日働いた人');
    }

    /**
     * 「翌日」を押したときに次の日の勤怠情報が表示される
     */
    public function test_admin_can_navigate_to_next_day()
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $user = User::factory()->create(['name' => '明日働く予定の人']);
        
        // 明日のデータを作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $tomorrow
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/attendance/list?date={$tomorrow}");

        $response->assertStatus(200);
        $response->assertSee($tomorrow);
        $response->assertSee('明日働く予定の人');
    }
}