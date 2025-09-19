<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>COACHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
<header>
    <a href="/">
        <img src="{{ asset('img/logo.svg') }}" alt="COACHTECHロゴ">
    </a>
</header>

<main>
    <section class="login__content">
        <h2 class="login-form__heading">ログイン</h2>

        {{-- 4) 認証が必要な操作から遷移してきた・各種ステータス表示 --}}
        @if (session('intended_protected'))
            <div class="form__notice" role="status">
                認証が必要な操作のため、ログインしてください。
            </div>
        @endif

        {{-- 5) メール未認証の誘導（コントローラ側でセット） --}}
        @if (session('must_verify'))
            <div class="form__notice" role="status">
                メール認証が完了していません。<br>
                <a class="link--primary" href="{{ route('verification.notice') }}">認証はこちらから</a>
            </div>
        @endif

        {{-- 3) 認証失敗などの全体エラー（コントローラでerrors(['auth' => ...])） --}}
        @if ($errors->has('auth'))
            <div class="form__error--global" role="alert">
                {{ $errors->first('auth') }} {{-- 例: ログイン情報が登録されていません --}}
            </div>
        @endif

        {{-- 6) 認証メール再送 成功メッセージ --}}
        @if (session('verification_link_sent'))
            <div class="form__notice" role="status">
                認証メールを再送しました。メールをご確認ください。
            </div>
        @endif

        {{-- 1) 入力フォーム --}}
        <form class="form" action="{{ route('login') }}" method="post" novalidate>
            @csrf

            <label class="form__group" for="email">
                <span class="form__label--item">メールアドレス</span>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                    inputmode="email"
                />
                {{-- 2) 3) バリデーションエラー表示（FormRequestの日本語文言が出ます） --}}
                @error('email')
                    <span class="form__error" role="alert">{{ $message }}</span>
                @enderror
            </label>

            <label class="form__group" for="password">
                <span class="form__label--item">パスワード</span>
                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                />
                @error('password')
                    <span class="form__error" role="alert">{{ $message }}</span>
                @enderror
            </label>

            <button class="form__button-submit" type="submit">ログインする</button>
        </form>

        {{-- 4) 会員登録画面への動線 --}}
        <p class="register__link">
            <a class="register__button-submit" href="{{ route('register') }}">会員登録はこちら</a>
        </p>
    </section>
</main>
</body>
</html>