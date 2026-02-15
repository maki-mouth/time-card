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
<header class="main-header">
    <a class="header__logo" href="/">
        <img src="{{ asset('img/COACHTECHヘッダーロゴ.png') }}" alt="ヘッダーロゴ">
    </a>
    <div class="search-area">
        <form action="{{ route('items.index') }}" method="GET" class="search-form">
            <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="なにをお探しですか?">
        </form>
    </div>
    <div class="actions">
        @auth
            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="action-link-out">ログアウト</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="action-link">ログイン</a>
        @endauth
        <a href="{{ route('mypage') }}" class="action-link">マイページ</a>
        <a href="{{ route('sell.create') }}" class="btn-primary">出品</a>
    </div>
</header>
<main>
    @yield('content')
</main>
</body>
</html>