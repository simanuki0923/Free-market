<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseAddressController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\ChatPreviewController;

Route::get('/', [ItemController::class, 'index'])
    ->name('item');

Route::get('/item/{item_id}', [ProductController::class, 'show'])
    ->whereNumber('item_id')
    ->name('item.show');

Route::middleware('auth')->group(function () {
    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage');

    Route::get('/mypage/profile', [ProfileController::class, 'edit'])->name('mypage.profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('/product/{product}/favorite', [FavoriteController::class, 'toggle'])
        ->whereNumber('product')
        ->name('product.favorite.toggle');

    Route::post('/item/{item_id}/comments', [CommentController::class, 'storeComment'])
        ->whereNumber('item_id')
        ->name('comments.store');

    Route::get('/purchase/{item_id}', [PurchaseController::class, 'show'])
        ->whereNumber('item_id')
        ->name('purchase');

    Route::get('/purchase/address/{item_id}', [PurchaseAddressController::class, 'edit'])
        ->whereNumber('item_id')
        ->name('purchase.address.edit');

    Route::patch('/purchase/address', [PurchaseAddressController::class, 'update'])
        ->name('purchase.address.update');

    Route::get('/payment/create', [PaymentController::class, 'create'])->name('payment.create');
    Route::post('/payment', [PaymentController::class, 'store'])->name('payment.store');
    Route::get('/payment/done', [PaymentController::class, 'done'])->name('payment.done');

    Route::get('/sell', [SellController::class, 'create'])->name('sell.create');
    Route::post('/sell', [SellController::class, 'store'])->name('sell.store');

    /**
     * 取引チャット（購入者 / 出品者）
     */
    Route::get('/chat/buyer/{transaction}', [ChatPreviewController::class, 'buyer'])
        ->whereNumber('transaction')
        ->name('chat.buyer');

    Route::get('/chat/seller/{transaction}', [ChatPreviewController::class, 'seller'])
        ->whereNumber('transaction')
        ->name('chat.seller');

    /**
     * メッセージ送信 / 編集（更新） / 削除
     * ※ インライン編集化により chat.message.edit (GET) は不要
     */
    Route::post('/chat/{transaction}/message', [ChatPreviewController::class, 'storeMessage'])
        ->whereNumber('transaction')
        ->name('chat.message.store');

    Route::patch('/chat/message/{message}', [ChatPreviewController::class, 'updateMessage'])
        ->whereNumber('message')
        ->name('chat.message.update');

    Route::delete('/chat/message/{message}', [ChatPreviewController::class, 'destroyMessage'])
        ->whereNumber('message')
        ->name('chat.message.destroy');

    /**
     * 本文下書き保存（本文のみ）
     */
    Route::post('/chat/{transaction}/draft', [ChatPreviewController::class, 'saveDraft'])
        ->whereNumber('transaction')
        ->name('chat.draft.save');

    /**
     * 取引完了（購入者）
     */
    Route::post('/chat/{transaction}/complete', [ChatPreviewController::class, 'completeTransaction'])
        ->whereNumber('transaction')
        ->name('chat.complete');

    /**
     * 評価送信（購入者 / 出品者）
     */
    Route::post('/chat/{transaction}/rate', [ChatPreviewController::class, 'storeRating'])
        ->whereNumber('transaction')
        ->name('chat.rate');
});