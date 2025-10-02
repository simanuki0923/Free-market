<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Product;
use App\Models\Sell;
use App\Models\Purchase;
use App\Models\Payment; // 任意

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret') ?? env('STRIPE_SECRET_KEY'));
        $this->middleware('auth');
    }

    /** カード入力画面：PaymentIntent 発行 → client_secret を渡す */
    public function create(Request $request)
    {
        $productId = $request->input('productId') ?? $request->input('item_id');
        abort_unless($productId, 404, '商品が指定されていません。');

        $product = Product::with('sell')->findOrFail((int)$productId);

        if ($product->user_id === Auth::id() || $product->is_sold) {
            return redirect()->route('item')->with('error', '購入できない商品です。');
        }

        $intent = PaymentIntent::create([
            'amount'   => (int)$product->price * 100, // 円→最小単位
            'currency' => 'jpy',
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'product_id' => (string)$product->id,
                'user_id'    => (string)Auth::id(),
            ],
        ]);

        return view('payment.create', [
            'product'       => $product,
            'clientSecret'  => $intent->client_secret,
            'paymentIntent' => $intent->id,
            'publicKey'     => config('services.stripe.public') ?? env('STRIPE_PUBLIC_KEY'),
        ]);
    }

    /** 決済成功後：purchases(必須) + payments(任意) 保存 */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'        => ['required','integer','exists:products,id'],
            'payment_intent_id' => ['required','string'],
        ]);

        return DB::transaction(function () use ($validated) {

            $product = Product::lockForUpdate()->with('sell')->findOrFail($validated['product_id']);

            if ($product->is_sold || $product->user_id === Auth::id()) {
                return redirect()->route('item')->with('error', 'この商品は購入できません。');
            }

            $pi = PaymentIntent::retrieve($validated['payment_intent_id']);
            if ($pi->status !== 'succeeded') {
                return back()->with('error', '決済が完了していません（status: '.$pi->status.'）。');
            }

            // sells が無ければ補完（不要なら存在しない場合はエラーで返す）
            $sell = $product->sell;
            if (!$sell) {
                $sell = Sell::create([
                    'user_id'    => $product->user_id,
                    'product_id' => $product->id,
                    'price'      => $product->price,
                ]);
            }

            // unique(user_id, sell_id) 事前ガード
            $exists = Purchase::where('user_id', Auth::id())->where('sell_id', $sell->id)->exists();
            if ($exists) {
                return redirect()->route('payment.done')->with('status', 'この出品は既に購入済みです。');
            }

            // purchases に挿入（必須列）
            $purchase = Purchase::create([
                'user_id'      => Auth::id(),
                'sell_id'      => $sell->id,
                'amount'       => (int)$product->price,
                'purchased_at' => now(),
            ]);

            // 任意：payments にも記録
            // Payment::create([
            //     'purchase_id'    => $purchase->id,
            //     'payment_method' => 'credit_card',
            //     'provider_txn_id'=> $pi->id,              // pi_...
            //     'paid_amount'    => (int)$product->price,
            //     'paid_at'        => now(),
            // ]);

            // SOLD
            $product->update(['is_sold' => true]);

            return redirect()->route('payment.done')->with('status', '決済が完了しました。');
        });
    }

    public function done()
    {
        return view('payment.done');
    }
}
