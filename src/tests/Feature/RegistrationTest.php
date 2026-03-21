<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class RegistrationTest extends TestCase
{
    use RefreshDatabase; // テストごとにDBをリセット

    /**
     * 名前が未入力の場合、バリデーションエラーが発生する
     */
    public function test_name_is_required()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    /**
     * メールアドレスが未入力の場合、バリデーションエラーが発生する
     */
    public function test_email_is_required()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * パスワードが未入力の場合、バリデーションエラーが発生する
     */
    public function test_password_is_required()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * パスワードが8文字未満の場合、バリデーションエラーが発生する
     */
    public function test_password_must_be_at_least_8_characters()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * パスワードが一致しない場合、バリデーションエラーが発生する
     */
    public function test_password_confirmation_must_match()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * 正常な入力の場合、データが保管されログイン後の画面へリダイレクトされる
     */
    public function test_user_can_register_with_valid_data()
    {
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        // 1. データベースに保存されているか確認
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'newuser@example.com',
        ]);

        // 2. ログイン状態になっているか確認
        $this->assertAuthenticated();

        // 3. リダイレクト先を確認（Fortifyのデフォルト設定に従う）
        $response->assertRedirect('/dashboard');
    }
}