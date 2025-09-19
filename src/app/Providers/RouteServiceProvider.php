<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    // Fortify 等が参照する HOME 定数だけ用意すれば十分
    public const HOME = '/';

    public function register(): void {}
    public function boot(): void {}
}
