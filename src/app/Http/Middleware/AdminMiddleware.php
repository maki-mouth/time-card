<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. ログインしているか？
        // 2. roleカラムが 'admin' か？
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request); // 条件クリア！次の処理（ページ表示）へ進む
        }

        // 条件に合わない場合はトップページへ強制送還
        return redirect('/login');
    }
}
