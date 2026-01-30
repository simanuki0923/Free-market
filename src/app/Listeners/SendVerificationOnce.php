<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Cache;

class SendVerificationOnce
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        // 既に認証済みなら送らない
        if (method_exists($user, 'hasVerifiedEmail') && $user->hasVerifiedEmail()) {
            return;
        }

        // 直近送信済みなら抑止（2分は再送しない。必要に応じて調整）
        $key = 'verify-mail:sent:' . $user->getKey();
        if (Cache::has($key)) {
            return;
        }

        // ここで1度だけ送る
        $user->sendEmailVerificationNotification();

        // 2分間は再送しない
        Cache::put($key, true, now()->addMinutes(2));
    }
}
