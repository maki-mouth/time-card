<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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

        Fortify::authenticateUsing(function ($request) {
            $user = User::where('email', $request->email)->first();

            // 【重要】パスワードの照合を追加！
            if ($user && Hash::check($request->password, $user->password)) {

                // 直前のページURLで「管理者画面からのログインか」を判定
                if (str_contains(url()->previous(), 'admin/login')) {
                    // 管理者画面からの場合、adminロール以外はログイン失敗(null)
                    if ($user->role !== 'admin') {
                        return null;
                    }
                } else {
                    // 一般ログイン画面からの場合、adminロールはログイン失敗(null)
                    if ($user->role === 'admin') {
                        return null;
                    }
                }

                return $user; // パスワードもロールも合っていればログイン許可
            }

            return null; // パスワードが違う、またはユーザーがいない場合は失敗
        });
    }
}