<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stripe\Stripe;

// Fortify 契約
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\FailedLoginResponse as FailedLoginResponseContract;

// 差し替え実装
use App\Actions\Fortify\LoginResponse;
use App\Actions\Fortify\FailedLoginResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ★ Fortify レスポンス差し替え
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(FailedLoginResponseContract::class, FailedLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 既存：Stripe 初期化（環境変数がある時だけ）
        if (config('services.stripe.secret')) {
            Stripe::setApiKey(config('services.stripe.secret'));
        }
    }
}
