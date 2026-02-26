<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\Facades\Auth;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        // ログアウト処理自体はすでに完了していますが、
        // セッションが切れる直前の情報や、リクエスト時の情報で判断します
        
        // もしリクエストURLに 'admin' が含まれている、
        // またはログアウトボタンに隠しパラメータを持たせる方法などがありますが、
        // 最も確実なのは「ログアウト前に判定してリダイレクト先を決める」ことです。
        
        // 今回は「管理者がログアウトボタンを押した」ことを判別できるようにします。
        return redirect($request->is('admin/*') || $request->has('admin') ? '/admin/login' : '/login');
    }
}