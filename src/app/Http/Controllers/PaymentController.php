<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Purchase;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Exception;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /** カード入力画面：ここで PaymentIntent を作成し client_secret を渡す */
    public function create(Request $request)
    {
        if (!Auth::check()) return redirect()->route('login');

        $productId = $request->input('productId') ?? $request->input('item_id');
        abort_unless($productId, 404);

        $product = Product::findOrFail($productId);

        if ($product->user_id === Auth::id() || $product->is_sold) {
            return redirect()->route('item')->with('flash_alert', '購入できない商品です。');
        }

        // ¥ → 最小単位（円×100）
        $amount = (int) ($product->price * 100);

        // 3DS を含むあらゆる支払い方式を Stripe 側に自動選択させる
        $intent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'jpy',
            'automatic_payment_methods' => ['enabled' => true],
            // 確認のために情報をメタデータに残す（任意）
            'metadata' => [
                'product_id' => (string)$product->id,
                'user_id'    => (string)Auth::id(),
            ],
        ]);

        // Blade に渡す：
        // - clientSecret: confirmCardPayment 用
        // - product: 完了後に保存する商品
        return view('payment.create', [
            'product'       => $product,
            'clientSecret'  => $intent->client_secret,
            'paymentIntent' => $intent->id, // 予備（不要なら渡さなくてOK）
        ]);
    }

    /**
     * 決済成功後のサーバ保存
     * フロントで confirmCardPayment() 成功後に
     *   POST /payment （product_id, payment_intent_id）を送る
     */
    public function store(Request $request)
    {
        if (!Auth::check()) return redirect()->route('login');

        $validated = $request->validate([
            'product_id'        => ['required', 'integer', 'exists:products,id'],
            'payment_intent_id' => ['required', 'string'],
        ]);

        $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

        if ($product->is_sold || $product->user_id === Auth::id()) {
            return redirect()->route('item')->with('flash_alert', 'この商品は購入できません。');
        }

        // PaymentIntent を取得して、本当に成功(succeeded)かサーバ側で確認
        $pi = PaymentIntent::retrieve($validated['payment_intent_id']);
        if ($pi->status !== 'succeeded') {
            // 3DS などで未完了の場合の保険。フロント成功時だけ来る想定だがチェックしておく
            return back()->with('flash_alert', '決済が完了していません（status: '.$pi->status.'）。');
        }

        // DB 保存
        Purchase::create([
            'user_id'        => Auth::id(),
            'product_id'     => $product->id,
            'price'          => $product->price,
            'status'         => 'paid',
            'payment_method' => 'credit_card',
            'purchase_date'  => now(),
        ]);

        $product->update(['is_sold' => true]);

        return redirect()->route('payment.done')->with('status', '決済が完了しました。');
    }

    public function done()
    {
        if (!Auth::check()) return redirect()->route('login');
        return view('payment.done');
    }
}
