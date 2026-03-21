<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者ログイン画面でメールアドレスが未入力の場合、エラーが発生する
     */
    public function test_admin_email_is_required_for_login()
    {
        // 管理者ログイン画面からのリクエストをシミュレート
        $response = $this->from('/admin/login')->post('/login', [
            'email' => '',
            'password' => 'admin_password',
        ]);

        $response->assertSessionHasErrors(['email']);
        // エラー時に管理者ログイン画面に戻っているか確認
        $response->assertRedirect('/admin/login');
    }

    /**
     * 管理者ログイン画面でパスワードが未入力の場合、エラーが発生する
     */
    public function test_admin_password_is_required_for_login()
    {
        $response = $this->from('/admin/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertRedirect('/admin/login');
    }

    /**
     * 管理者の登録内容と一致しない場合、エラーが発生する
     */
    public function test_admin_login_fails_with_invalid_credentials()
    {
        // 1. テスト用の管理者ユーザーを作成（roleがadminなどのフラグがある場合）
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('correct_admin_pass'),
            'role' => 'admin', // もしroleカラムがあるなら追加
        ]);

        // 2. 誤ったパスワードでログイン試行
        $response = $this->from('/admin/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong_pass',
        ]);

        // 3. 認証失敗を確認
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
        $response->assertRedirect('/admin/login');
    }
}