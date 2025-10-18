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
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseAddressController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SellController;

Route::get('/', [ItemController::class, 'index'])
    ->name('item');

Route::get('/item/{item_id}', [ProductController::class, 'show'])
    ->whereNumber('item_id')
    ->name('item.show');

Route::get('/search', [SearchController::class, 'index'])->name('search');

Route::middleware('auth')->group(function () {
    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage');

    Route::get('/mypage/profile', [ProfileController::class, 'edit'])->name('mypage.profile');
    Route::patch('/profile',      [ProfileController::class, 'update'])->name('profile.update');

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
    Route::patch('/purchase/address',     [PurchaseAddressController::class, 'update'])
        ->name('purchase.address.update');

    Route::get('/payment/create', [PaymentController::class, 'create'])->name('payment.create');
    Route::post('/payment',       [PaymentController::class, 'store'])->name('payment.store');
    Route::get('/payment/done',   [PaymentController::class, 'done'])->name('payment.done');

    Route::get('/sell',  [SellController::class, 'create'])->name('sell.create');
    Route::post('/sell', [SellController::class, 'store'])->name('sell.store');
});
