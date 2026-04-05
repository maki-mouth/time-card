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
            if (request()->is('admin/*')) {
                return view('admin.auth.login');
            }
            return view('user.auth.login');
        });

        Fortify::verifyEmailView(function () {
            return view('user.auth.verify-email');
        });

        Fortify::authenticateUsing(function ($request) {

            $loginRequest = new \App\Http\Requests\LoginRequest();

            \Illuminate\Support\Facades\Validator::make(
                $request->all(),
                $loginRequest->rules(),
                $loginRequest->messages()
            )->validate();

            $user = User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {

                if (str_contains(url()->previous(), 'admin/login')) {
                    if ($user->role !== 'admin') {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'email' => ['管理者権限がありません。'],
                        ]);
                    }
                } else {
                    if ($user->role === 'admin') {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'email' => ['管理者の方は管理者ログイン画面からログインしてください。'],
                        ]);
                    }
                }
                return $user;
            }

            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        });
    }
}
