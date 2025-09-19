<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>COACHTECH｜会員登録</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body>
<header>
    <a href="/">
        <img src="{{ asset('img/logo.svg') }}" alt="COACHTECHロゴ">
    </a>
</header>

<main>
    <section class="register__content">
        <h2 class="register-form__heading">会員登録</h2>

        {{-- バリデーションメッセージについて
             想定文言：
             ・お名前を入力してください
             ・メールアドレスを入力してください
             ・メールアドレスはメール形式で入力してください
             ・パスワードを入力してください
             ・パスワードは8文字以上で入力してください
             ・パスワードと一致しません
             ※上記はコントローラ/フォームリクエスト側でメッセージを設定してください。
        --}}

        {{-- フラッシュメッセージ（任意） --}}
        @if (session('status'))
            <div class="flash__message" role="status">{{ session('status') }}</div>
        @endif

        <form class="form" action="{{ route('register') }}" method="post" novalidate>
            @csrf

            {{-- ユーザー名 --}}
            <label class="form__group">
                <span class="form__label--item">ユーザー名</span>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    autocomplete="name"
                    required
                    aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('name') ? 'error-name' : '' }}"
                />
                @if ($errors->has('name'))
                    @foreach ($errors->get('name') as $msg)
                        <span id="error-name" class="form__error">{{ $msg }}</span>
                    @endforeach
                @endif
            </label>

            {{-- メールアドレス --}}
            <label class="form__group">
                <span class="form__label--item">メールアドレス</span>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('email') ? 'error-email' : '' }}"
                />
                @if ($errors->has('email'))
                    @foreach ($errors->get('email') as $msg)
                        <span id="error-email" class="form__error">{{ $msg }}</span>
                    @endforeach
                @endif
            </label>

            {{-- パスワード --}}
            <label class="form__group">
                <span class="form__label--item">パスワード</span>
                <input
                    type="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('password') ? 'error-password' : '' }}"
                />
                @if ($errors->has('password'))
                    @foreach ($errors->get('password') as $msg)
                        <span id="error-password" class="form__error">{{ $msg }}</span>
                    @endforeach
                @endif
            </label>

            {{-- 確認用パスワード --}}
            <label class="form__group">
                <span class="form__label--item">確認用パスワード</span>
                <input
                    type="password"
                    name="password_confirmation"
                    minlength="8"
                    autocomplete="new-password"
                    required
                    aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('password_confirmation') ? 'error-password-confirmation' : '' }}"
                />
                @if ($errors->has('password_confirmation'))
                    @foreach ($errors->get('password_confirmation') as $msg)
                        <span id="error-password-confirmation" class="form__error">{{ $msg }}</span>
                    @endforeach
                @endif
            </label>

            <button class="form__button-submit" type="submit">登録する</button>
        </form>

        <p class="login__link">
            <a class="login__button-submit" href="{{ route('login') }}">ログインはこちら</a>
        </p>

        {{-- 参考：
             ・登録直後はコントローラ側でプロフィール設定画面へリダイレクトしてください。
             ・メール認証を導入している場合は、認証用画面へリダイレクトしてください。
        --}}
    </section>
</main>
</body>
</html>