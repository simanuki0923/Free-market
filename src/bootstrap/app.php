<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        App\Providers\AppServiceProvider::class,
        App\Providers\EventServiceProvider::class,    // ★ 追加：Registered -> Verify通知
        App\Providers\FortifyServiceProvider::class,  // ★ 追加：登録後のリダイレクト差し替え
        // App\Providers\AuthServiceProvider::class,  // 使っていれば
        // App\Providers\RouteServiceProvider::class, // 使っていれば
    ])
    ->create();
