<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Profile;

class PurchaseController extends Controller
{
    /** 購入画面表示（商品サマリ & 支払い方法セレクト） */
    public function show(int $item_id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $product = Product::with('sell')->findOrFail($item_id);

        // 自分の商品 or 売切れは一覧へ
        if ($product->user_id === Auth::id() || $product->is_sold || optional($product->sell)->is_sold) {
            return redirect()->route('item')->with('error', '購入できない商品です。');
        }

        $profile = Profile::where('user_id', Auth::id())->first();
        $payment = (object)['payment_method' => 'convenience_store']; // 初期表示用

        return view('purchase', compact('product', 'profile', 'payment'));
    }
}
