<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    /** 購入画面表示（商品サマリ & 支払い方法セレクト） */
    public function show(int $item_id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $product = Product::findOrFail($item_id);

        // 自分の商品 or 売切れは一覧へ
        if ($product->user_id === Auth::id() || $product->is_sold) {
            return redirect()->route('item')->with('error', '購入できない商品です。');
        }

        $profile = Profile::where('user_id', Auth::id())->first();
        // 右側サマリ初期表示用（画面で変更可能）
        $payment = (object)['payment_method' => 'convenience_store'];

        return view('purchase', compact('product', 'profile', 'payment'));
    }
}
