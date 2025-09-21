<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

use App\Http\Controllers\ItemController;        // おすすめ（全商品）
use App\Http\Controllers\ItemListController;    // マイリスト（ログイン時のみ）
use App\Http\Controllers\SearchController;      // 検索
use App\Http\Controllers\ProductController;     // 商品詳細
use App\Http\Controllers\ProfileController;     // プロフィール編集
use App\Http\Controllers\FavoriteController;    // お気に入りトグル

/*
|--------------------------------------------------------------------------
| Public: 一覧 / 検索 / 詳細
|--------------------------------------------------------------------------
*/

/**
 * 一覧（/?tab=all | /?tab=mylist）
 * 未ログインで tab=mylist の場合も「おすすめ（全商品）」を出す
 */
Route::get('/', function (Request $request) {
    $tab = strtolower($request->query('tab', 'all')); // デフォルトはおすすめ

    // 未ログイン && tab=mylist → 全商品（ItemController@index）
    if ($tab === 'mylist' && !Auth::check()) {
        return app(ItemController::class)->index($request);
    }

    // ログイン済み && tab=mylist → マイリスト（ItemListController@index）
    if ($tab === 'mylist') {
        return app(ItemListController::class)->index($request);
    }

    // それ以外はおすすめ（全商品）
    return app(ItemController::class)->index($request);
})->name('item');

/** 検索（未ログインOK） */
Route::get('/search', [SearchController::class, 'index'])->name('search');

/** 商品詳細（未ログインOK） /item/{item_id} */
Route::get('/item/{item_id}', [ProductController::class, 'show'])
    ->whereNumber('item_id')
    ->name('item.show');

/** お気に入りトグル（ログイン必須） */
Route::post('/favorites/{product}', [FavoriteController::class, 'toggle'])
    ->middleware('auth')
    ->name('favorites.toggle');

/*
|--------------------------------------------------------------------------
| Auth: メール認証 / プロフィール
|--------------------------------------------------------------------------
*/

/** 認証メール誘導（ログイン必須） */
Route::get('/email/verify', fn () => view('auth.verify-email'))
    ->middleware('auth')
    ->name('verification.notice');

/** メール内リンクでの検証（署名必須）完了後はプロフィール編集へ */
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();  // email_verified_at をセット
    return redirect()->route('profile.edit');
})->middleware(['auth', 'signed'])
  ->name('verification.verify');

/** 検証メールの再送（レート制限あり） */
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])
  ->name('verification.send');

/** プロフィール編集ページ（ログイン必須） */
Route::get('/profile/edit', [ProfileController::class, 'edit'])
    ->middleware('auth')
    ->name('profile.edit');

/** プロフィール更新（PUT/PATCH 両対応・ログイン必須） */
Route::match(['put', 'patch'], '/profile', [ProfileController::class, 'update'])
    ->middleware('auth')
    ->name('profile.update');
