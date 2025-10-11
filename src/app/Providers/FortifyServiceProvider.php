<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Actions\Fortify\RegisterResponse;
use App\Actions\Fortify\LoginResponse;
use App\Actions\Fortify\VerifyEmailResponse;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ★ レスポンス差し替え（既存のままでOK）
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
    }

    public function boot(): void
    {
        // --- Fortify アクション ---
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // --- View 紐づけ ---
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn ($request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));

        // --- ログイン時のバリデーション＋認証 ---
        Fortify::authenticateUsing(function (Request $request) {
            // LoginRequest の前処理/ルール/メッセージを適用
            app(LoginRequest::class)
                ->setContainer(app())
                ->setRedirector(app('redirect'))
                ->validateResolved();

            $email = (string) mb_strtolower(trim((string) $request->input('email')));
            $password = (string) $request->input('password');

            /** @var \App\Models\User|null $user */
            $user = User::where('email', $email)->first();

            if ($user && Hash::check($password, $user->password)) {
                // （必要なら）メール認証の強制チェック
                // if (! $user->hasVerifiedEmail()) {
                //     throw ValidationException::withMessages([
                //         'auth' => 'メール認証がまだ完了していません',
                //     ]);
                // }
                return $user; // 成功
            }

            // ★ 失敗：必ず 'auth' キーでテスト期待文言を投げる（←ここが決め手）
            throw ValidationException::withMessages([
                'auth' => 'ログイン情報が登録されていません',
            ]);
        });

        // --- レート制限 ---
        RateLimiter::for('login', function (Request $request) {
            $key = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());
            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by((string) $request->session()->get('login.id'));
        });
    }
}
