<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>COACHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <!-- ロゴ -->
            <a href="/" class="header__logo">
                <img src="{{ asset('img/logo.svg') }}" alt="COACHTECHロゴ">
            </a>

            <!-- 検索フォーム -->
            <form action="{{ route('search') }}" method="GET" class="header__search">
                <input type="text" name="search" placeholder="何をお探しですか？" value="{{ request('search') }}">
            </form>

            <!-- ナビゲーション -->
            <nav class="header__nav">
                <ul class="nav__list">
                    @if (Auth::check())
                        <!-- ログイン時 -->
                        <form action="/logout" method="post" class="nav__form">
                            @csrf
                            <li class="nav__item">
                                <button type="submit" class="nav__item-button">ログアウト</button>
                            </li>
                        </form>
                        <li class="nav__item">
                            <a href="/mypage" class="nav__item-link">マイページ</a>
                        </li>
                    @else
                        <!-- 未ログイン時 -->
                        <li class="nav__item">
                            <a href="/login" class="nav__item-link">ログイン</a>
                        </li>
                        <li class="nav__item">
                            <a href="/register" class="nav__item-link">会員登録</a>
                        </li>
                    @endif
                    <!-- 出品ボタン -->
                    <li class="nav__item">
                        <a href="{{ route('sell.create') }}" class="nav__item-link nav__item-link-sell">出品</a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>
</html>