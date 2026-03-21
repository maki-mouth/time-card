<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メールアドレスが未入力の場合、バリデーションエラーが発生する
     */
    public function test_email_is_required_for_login()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * パスワードが未入力の場合、バリデーションエラーが発生する
     */
    public function test_password_is_required_for_login()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * 登録内容（メールまたはパスワード）と一致しない場合、エラーが発生する
     */
    public function test_login_fails_with_invalid_credentials()
    {
        // 1. テスト用のユーザーを一人作成しておく
        $user = User::factory()->create([
            'email' => 'correct@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        // 2. 違うパスワードでログインを試みる
        $response = $this->post('/login', [
            'email' => 'correct@example.com',
            'password' => 'wrong_password',
        ]);

        // 3. エラーがあることを確認
        // Fortify（Laravel）の標準では、認証失敗時は 'email' フィールドにエラーが紐付きます
        $response->assertSessionHasErrors(['email']);
        
        // 4. まだログイン状態になっていないことを確認
        $this->assertGuest();
    }
}