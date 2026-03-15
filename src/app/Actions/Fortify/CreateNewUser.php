<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        // 1. フォームリクエストをインスタンス化
        $request = new RegisterRequest();

        // 2. フォームリクエストで定義したルールとメッセージでバリデーション実行
        Validator::make(
            $input,
            $request->rules(),
            $request->messages()
        )->validate();

        // 3. 登録処理
        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => 'user',
        ]);
    }
}