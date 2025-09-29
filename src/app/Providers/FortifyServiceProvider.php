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
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ★ Fortify のレスポンス差し替え
        // 登録直後：/email/verify（認証依頼）へ
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);

        // 通常ログイン：/（mypage等、LoginResponse側で定義した先）へ
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);

        // 認証リンク成功：/profile（VerifyEmailResponse側で定義）へ
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // --- Fortify の各種アクション（ユーザ作成・更新など） ---
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // --- View 紐づけ（resources/views/auth/*.blade.php を想定） ---
        Fortify::loginView(fn () => view('auth.login'));                               // GET /login
        Fortify::registerView(fn () => view('auth.register'));                         // GET /register
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));  // GET /forgot-password
        Fortify::resetPasswordView(fn ($request) => view('auth.reset-password', ['request' => $request])); // GET /reset-password/{token}
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));                  // GET /email/verify

        // --- ログイン時のバリデーション＋認証 ---
        // LoginRequest（app/Http/Requests/LoginRequest.php）を必ず通す
        Fortify::authenticateUsing(function (Request $request) {
            // LoginRequest を起動し、rules()/messages()/prepareForValidation() を適用
            app(LoginRequest::class)
                ->setContainer(app())
                ->setRedirector(app('redirect'))
                ->validateResolved();

            // ↓ ここまで来れば email/password の必須 & 形式OK
            $email = (string) $request->input('email');
            $password = (string) $request->input('password');

            /** @var \App\Models\User|null $user */
            $user = User::where('email', $email)->first();

            if ($user && Hash::check($password, $user->password)) {
                // （任意）メール認証を強制する場合はコメント解除
                // if (! $user->hasVerifiedEmail()) {
                //     return null; // 未認証は弾く
                // }

                return $user; // ← 認証成功
            }

            // 認証失敗（Fortify側で既定のエラー応答）
            return null;
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
