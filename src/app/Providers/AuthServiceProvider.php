<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 認証メールの文面・件名・ボタン等を上書き
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('【Free-market】メールアドレスの確認のお願い')
                ->greeting("{$notifiable->name} さん、こんにちは")
                ->line('アカウントの有効化のため、以下のボタンからメールアドレスを確認してください。')
                ->action('メールアドレスを確認する', $url)
                ->line('このメールに心当たりがない場合は破棄してください。')
                ->salutation('Free-market サポート');
        });
    }
}
