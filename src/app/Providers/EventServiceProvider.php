<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use App\Listeners\SendVerificationOnce;

class EventServiceProvider extends ServiceProvider
{
    // 定義ベースの競合を避けるため空にする（ここに何も書かない）
    protected $listen = [];

    // 明示しておく（親がstaticなので合わせてもOK／無くても可）
    protected static $shouldDiscoverEvents = false;

    public function boot(): void
    {
        // すべての ServiceProvider がbootした“後に”実行して順序に勝つ
        $this->app->booted(function () {
            // Registered に既にぶら下がるリスナーを全て解除（重複を掃除）
            Event::forget(Registered::class);

            // 必要な1本だけ（冪等リスナー）を登録
            Event::listen(Registered::class, SendVerificationOnce::class);
        });
    }
}
