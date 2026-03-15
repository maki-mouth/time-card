<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Requests\LoginRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Fortify;
use App\Http\Responses\LogoutResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(\Laravel\Fortify\Http\Requests\LoginRequest::class, \App\Http\Requests\LoginRequest::class);

        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function () {
            return view('user.auth.register');
        });

        Fortify::loginView(function () {
            // 表示するViewの切り分け（ここはOKです！）
            if (request()->is('admin/*')) {
                return view('admin.auth.login');
            }
            return view('user.auth.login');
        });

        // メール認証待ち画面のパスを指定（Mailtrap連携時に必要）
        Fortify::verifyEmailView(function () {
            return view('user.auth.verify-email');
        });

        Fortify::authenticateUsing(function ($request) {

            // 1. LoginRequestのインスタンスを作成
            $loginRequest = new \App\Http\Requests\LoginRequest();

            // 2. フォームリクエストで定義したルールとメッセージでバリデーションを実行
            // validate() を呼ぶことで、失敗時は自動的にエラーメッセージと共に元の画面へ戻ります
            \Illuminate\Support\Facades\Validator::make(
                $request->all(),
                $loginRequest->rules(),
                $loginRequest->messages()
            )->validate();

            $user = User::where('email', $request->email)->first();

            // 【重要】パスワードの照合を追加！
            if ($user && Hash::check($request->password, $user->password)) {

            // 管理者画面からのログイン判定
                if (str_contains(url()->previous(), 'admin/login')) {
                    if ($user->role !== 'admin') {
                        // 🛑 管理者でない場合は、日本語でエラーを投げる
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'email' => ['管理者権限がありません。'],
                        ]);
                    }
                } else {
                    // 一般画面からのログイン判定
                    if ($user->role === 'admin') {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'email' => ['管理者の方は管理者ログイン画面からログインしてください。'],
                        ]);
                    }
                }
                return $user;
            }

            // パスワード間違いなどの場合
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => [trans('auth.failed')], // lang/ja/auth.php のメッセージを表示
            ]);
        });
    }
}
