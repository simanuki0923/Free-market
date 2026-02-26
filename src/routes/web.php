<?php

use App\Http\Controllers\ChatDraftController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\ChatScreenController;
use App\Http\Controllers\ChatTransactionController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseAddressController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SellController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ItemController::class, 'index'])
    ->name('item');

Route::get('/item/{item_id}', [ProductController::class, 'show'])
    ->whereNumber('item_id')
    ->name('item.show');

Route::middleware('auth')->group(function (): void {
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

    Route::get('/chat/buyer/{transaction}', [ChatScreenController::class, 'buyer'])
        ->whereNumber('transaction')
        ->name('chat.buyer');

    Route::get('/chat/seller/{transaction}', [ChatScreenController::class, 'seller'])
        ->whereNumber('transaction')
        ->name('chat.seller');

    Route::post('/chat/{transaction}/message', [ChatMessageController::class, 'store'])
        ->whereNumber('transaction')
        ->name('chat.message.store');

    Route::patch('/chat/message/{message}', [ChatMessageController::class, 'update'])
        ->whereNumber('message')
        ->name('chat.message.update');

    Route::delete('/chat/message/{message}', [ChatMessageController::class, 'destroy'])
        ->whereNumber('message')
        ->name('chat.message.destroy');

    Route::post('/chat/{transaction}/draft', [ChatDraftController::class, 'store'])
        ->whereNumber('transaction')
        ->name('chat.draft.save');

    Route::post('/chat/{transaction}/complete', [ChatTransactionController::class, 'complete'])
        ->whereNumber('transaction')
        ->name('chat.complete');

    Route::post('/chat/{transaction}/rate', [ChatTransactionController::class, 'rate'])
        ->whereNumber('transaction')
        ->name('chat.rate');
});