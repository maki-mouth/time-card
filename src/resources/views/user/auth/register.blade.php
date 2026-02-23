@extends('layouts.user')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endsection

@section('content')
<div class="register-container">
    <h2 class="register-title">会員登録</h2>

    <form class="register-form" action="/register" method="POST">
        @csrf

        {{-- 名前 --}}
        <div class="form-group">
            <label for="name">名前</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}">
            @error('name')
                <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- メールアドレス --}}
        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}">
            @error('email')
                <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- パスワード --}}
        <div class="form-group">
            <label for="password">パスワード</label>
            <input type="password" name="password" id="password">
            @error('password')
                <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- パスワード確認 --}}
        <div class="form-group">
            <label for="password_confirmation">パスワード確認</label>
            <input type="password" name="password_confirmation" id="password_confirmation">
        </div>

        {{-- 登録ボタン --}}
        <div class="form-actions">
            <button type="submit" class="btn-register">登録する</button>
        </div>
    </form>

    {{-- ログインリンク --}}
    <div class="login-link">
        <a href="/login">ログインはこちら</a>
    </div>
</div>
@endsection