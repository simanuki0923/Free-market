<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CommentController;

// ★ 追加
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseAddressController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SellController;

/**
 * トップ（タブ切替）
 */
Route::get('/', [ItemController::class, 'index'])
    ->name('item');

/** 商品詳細 */
Route::get('/item/{item_id}', [ProductController::class, 'show'])
    ->whereNumber('item_id')
    ->name('item.show');

/** 検索 */
Route::get('/search', [SearchController::class, 'index'])->name('search');

Route::middleware('auth')->group(function () {
    /** マイページ */
    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage');

    /** プロフィール編集・更新 */
    Route::get('/mypage/profile', [ProfileController::class, 'edit'])->name('mypage.profile');
    Route::patch('/profile',      [ProfileController::class, 'update'])->name('profile.update');

    /** お気に入りトグル */
    Route::post('/product/{product}/favorite', [FavoriteController::class, 'toggle'])
        ->whereNumber('product')
        ->name('product.favorite.toggle');

    /** コメント投稿 */
    Route::post('/item/{item_id}/comments', [CommentController::class, 'storeComment'])
        ->whereNumber('item_id')
        ->name('comments.store');

    /** ★ 購入フロー */
    // 購入画面表示（product.blade.php の「購入」ボタンの遷移先）
    Route::get('/purchase/{item_id}', [PurchaseController::class, 'show'])
        ->whereNumber('item_id')
        ->name('purchase');

    // 配送先編集
    Route::get('/purchase/address/{item_id}', [PurchaseAddressController::class, 'edit'])
        ->whereNumber('item_id')
        ->name('purchase.address.edit');
    Route::patch('/purchase/address',     [PurchaseAddressController::class, 'update'])
        ->name('purchase.address.update');

    // 決済（Stripe 想定）
    Route::get('/payment/create', [PaymentController::class, 'create'])->name('payment.create');
    Route::post('/payment',       [PaymentController::class, 'store'])->name('payment.store');
    Route::get('/payment/done',   [PaymentController::class, 'done'])->name('payment.done');

    /** ★ 出品フロー（今回追加） */
    Route::get('/sell',  [SellController::class, 'create'])->name('sell.create'); // 出品フォーム表示
    Route::post('/sell', [SellController::class, 'store'])->name('sell.store');   // 出品登録
});
