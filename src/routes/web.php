<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;

Route::get('/', [MypageController::class, 'index'])->name('mypage');
Route::get('/search', [SearchController::class, 'index'])->name('search');

// 認証案内（ログイン必須）
Route::get('/email/verify', fn () => view('auth.verify-email'))
    ->middleware('auth')->name('verification.notice');

// ★ 認証リンク成功時：初回設定へ
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill(); // email_verified_at をセット
    return redirect()->route('profile.edit'); // ← ここを /profile/edit に
})->middleware(['auth', 'signed'])->name('verification.verify');

// 認証メール再送
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// プロフィール初回設定（要ログイン）
Route::get('/profile/edit', [ProfileController::class, 'edit'])
    ->middleware('auth')
    ->name('profile.edit');

// ★更新（PUT|PATCH）※送信先は /profile
Route::match(['put','patch'], '/profile', [ProfileController::class, 'update'])
    ->middleware('auth')
    ->name('profile.update');