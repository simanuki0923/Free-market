<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    public function boot(): void
    {
        // 保険：ユーザー作成直後に必ず認証メールを送る
        User::created(function (User $user) {
            if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
            }
        });
    }
}
