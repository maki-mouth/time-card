@extends('layouts.user')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('content')
<main>
    <div class="message-box">
        <p>登録していただいたメールアドレスに認証メールを送付しました。</p>
        <p>メール認証を完了してください。</p>
    </div>
    <a href="https://mailtrap.io/inboxes" target="_blank" class="instruction">認証はこちらから</a>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="resend-button">認証メールを再送する</button>
    </form>
</main>
@endsection