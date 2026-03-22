<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** 1. 会員登録後、認証メールが送信される */
    public function test_verification_email_is_sent_after_registration()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 登録後にメール通知（VerifyEmail）が送られたか確認
        Notification::assertSentTo(
            User::where('email', 'test@example.com')->first(),
            VerifyEmail::class
        );
    }

    /** 2. メール認証誘導画面で「再送」などのアクションが正常か */
    // ※「認証はこちらから」ボタンは通常メール内にありますが、
    // 画面上の「認証メール再送」などの挙動を確認するテストです
    public function test_verification_screen_can_be_rendered()
    {
        $user = User::factory()->create([
            'email_verified_at' => null, // 未認証状態
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertSee('認証メールを送付しました'); // 画面上の文言に合わせて調整
    }

    /** 3. メール認証を完了すると、勤怠打刻画面に遷移する */
    public function test_email_can_be_verified_and_redirects_to_attendance()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Laravel標準の署名付き認証URLを生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        // 1. データベースで認証済み（email_verified_atが埋まっている）か確認
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        // 2. 勤怠打刻画面（'/' など）にリダイレクトされるか確認
        $response->assertRedirect('/dashboard'); // 認証後の遷移先URLに合わせて調整
    }
}