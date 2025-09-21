<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemListController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MypageController;

/**
 * 一覧（トップ）
 * 例：/?tab=mylist でログイン時はマイリスト、未ログイン時は全商品
 */
Route::get('/', function (Request $request) {
    $tab = strtolower($request->query('tab', 'all'));

    if ($tab === 'mylist' && Auth::check()) {
        return app(ItemListController::class)->index($request); // ログイン時：マイリスト
    }
    // それ以外は全商品
    return app(ItemController::class)->index($request);
})->name('item');

/** 商品詳細 */
Route::get('/item/{item_id}', [ProductController::class, 'show'])->name('item.show');

/** 検索 */
Route::get('/search', [SearchController::class, 'index'])->name('search');

/** プロフィール編集（ヘッダーの「プロフィールを編集」用） */
Route::middleware('auth')->group(function () {
    // マイページ（app.blade.php の「マイページ」リンクは route('mypage') へ）
    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',      [ProfileController::class, 'update'])->name('profile.update');
}

);
