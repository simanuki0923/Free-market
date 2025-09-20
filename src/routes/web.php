<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

use App\Http\Controllers\MypageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;

/**
 * 一覧・検索（未ログインOK）
 */
Route::get('/', [MypageController::class, 'index'])->name('mypage');
Route::get('/search', [SearchController::class, 'index'])->name('search');

/**
 * 商品詳細（未ログインOK）
 * 画像／商品名クリックで /product/{id} へ遷移
 */
Route::get('/product/{product}', [ProductController::class, 'show'])
    ->whereNumber('product')
    ->name('product');

/**
 * 認証メール関連（ログイン必須）
 */
Route::get('/email/verify', fn () => view('auth.verify-email'))
    ->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();                 // email_verified_at セット
    return redirect()->route('profile.edit'); // 初回設定へ
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

/**
 * プロフィール初回設定・更新（ログイン必須）
 */
Route::get('/profile/edit', [ProfileController::class, 'edit'])
    ->middleware('auth')->name('profile.edit');

Route::match(['put','patch'], '/profile', [ProfileController::class, 'update'])
    ->middleware('auth')->name('profile.update');
