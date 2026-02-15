<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>flea-market</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('css')
</head>

<body>
<header class="header">
    <a class="header__logo" href="/">
        <img src="{{ asset('img/COACHTECHヘッダーロゴ.png') }}" alt="ヘッダーロゴ">
    </a>
    <div class="actions">
        @auth
        <a href="" class="action-link">勤怠</a>
        <a href="" class="action-link">勤怠一覧</a>
        <a href="" class="action-link">申請</a>
        <form method="POST" action="" class="logout-form">
            @csrf
            <button type="submit" class="action-link-out">ログアウト</button>
        </form>
        @endauth
    </div>
</header>
<main>
    @yield('content')
</main>
</body>
</html>