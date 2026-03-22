<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Tests\TestCase;
use Carbon\Carbon;

class AdminStaffTest extends TestCase
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

    /** 1. 管理者が一般ユーザーの「氏名」「メールアドレス」を確認できる */
    public function test_admin_can_view_user_profile_info()
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/staff/list");

        $response->assertStatus(200);
        $response->assertSee($this->staff->name);
        $response->assertSee($this->staff->email);
    }

    /** 2. ユーザーの勤怠情報が正しく表示される */
    public function test_admin_can_view_correct_attendance_data()
    {
        // テスト用の勤怠データを作成（2026年3月のデータとする）
        Attendance::factory()->create([
            'user_id' => $this->staff->id,
            'date' => '2026-03-21',
            'check_in' => '2026-03-21 09:00:00',
            'check_out' => '2026-03-21 18:00:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/attendance/staff/{$this->staff->id}?month=2026-03");

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** 3. 「前月」を押したときに表示月の前月の情報が表示される */
    public function test_admin_can_navigate_to_previous_month()
    {
        // 3月の画面を表示している状態で、2月のパラメータを送る
        $response = $this->actingAs($this->admin)
            ->get("/admin/attendance/staff/{$this->staff->id}?month=2026-02");

        $response->assertStatus(200);
        // 画面内に「2026年2月」という文字列があるか（Viewの表記に合わせて調整してください）
        $response->assertSee('2026/02');
    }

    /** 4. 「翌月」を押したときに表示月の翌月の情報が表示される */
    public function test_admin_can_navigate_to_next_month()
    {
        // 3月の画面を表示している状態で、4月のパラメータを送る
        $response = $this->actingAs($this->admin)
            ->get("/admin/attendance/staff/{$this->staff->id}?month=2026-04");

        $response->assertStatus(200);
        $response->assertSee('2026/04');
    }

    /** 5. 「詳細」を押すとその日の勤怠詳細画面に遷移する */
    public function test_admin_can_click_detail_and_navigate()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->staff->id,
            'date' => '2026-03-21',
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/attendance/staff/{$this->staff->id}");

        // 勤怠詳細（editDay/show）へのリンクが存在するか確認
        // ルート名が admin.attendance.show の場合
        $detailUrl = route('admin.attendance.show', ['id' => $attendance->id]);
        
        $response->assertSee($detailUrl);
        
        // 実際にそのURLにアクセスできるか
        $this->get($detailUrl)->assertStatus(200);
    }
}