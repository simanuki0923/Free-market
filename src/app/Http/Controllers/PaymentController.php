<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

use App\Models\Product;
use App\Models\Sell;
use App\Models\Purchase;
use App\Models\Payment;

use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        if (!App::environment('testing')) {
            $secret = config('services.stripe.secret') ?? env('STRIPE_SECRET_KEY');
            if ($secret) {
                Stripe::setApiKey($secret);
            }
        }
    }

    public function create(Request $request)
    {
        $productId      = $request->input('productId') ?? $request->input('item_id');
        $selectedMethod = $request->input('payment_method');

        abort_unless($productId, 404, '商品が指定されていません。');

        $product = Product::query()->findOrFail((int)$productId);

        if ($product->user_id === Auth::id() || $product->is_sold) {
            return redirect()
                ->route('item')
                ->with('error', '購入できない商品です。');
        }

        if (empty($selectedMethod)) {
            return redirect()
                ->route('item')
                ->with('error', '支払い方法が選択されていません。');
        }

        if (App::environment('testing')) {
            try {
                DB::transaction(function () use ($product, $selectedMethod) {

                    $p = Product::lockForUpdate()
                        ->with('sell')
                        ->findOrFail($product->id);

                    if ($p->is_sold) {
                        return;
                    }

                    $sell = $p->sell;
                    if (!$sell) {
                        $sell = Sell::create([
                            'user_id'     => $p->user_id,
                            'product_id'  => $p->id,
                            'category_id' => $p->category_id ?? null,
                            'name'        => $p->name,
                            'brand'       => $p->brand ?? null,
                            'price'       => (int)$p->price,
                            'image_path'  => $p->image_path ?? null,
                            'condition'   => $p->condition ?? null,
                            'description' => $p->description ?? null,
                            'is_sold'     => false,
                        ]);
                    }

                    $purchase = Purchase::updateOrCreate(
                        [
                            'user_id' => Auth::id(),
                            'sell_id' => $sell->id,
                        ],
                        [
                            'amount'          => (int)$p->price,
                            'payment_method'  => $selectedMethod,
                            'purchased_at'    => now(),
                        ]
                    );

                    Payment::updateOrCreate(
                        [
                            'purchase_id' => $purchase->id,
                        ],
                        [
                            'payment_method'  => $selectedMethod,
                            'provider_txn_id' => 'test-intent',
                            'paid_amount'     => (int)$p->price,
                            'paid_at'         => now(),
                        ]
                    );

                    // SOLD化
                    $p->update(['is_sold' => true]);
                    $sell->update(['is_sold' => true]);
                });

                return redirect()
                    ->route('payment.done')
                    ->with([
                        'status'          => 'テスト決済が完了しました。',
                        'payment_method'  => $selectedMethod,
                    ]);

            } catch (\Throwable $e) {
                Log::warning('Test finalize failed: ' . $e->getMessage());
                return redirect()
                    ->route('item')
                    ->with('error', 'テスト決済処理に失敗しました。');
            }
        }

        if ($selectedMethod === 'convenience_store') {
            try {
                DB::transaction(function () use ($product, $selectedMethod) {

                    $p = Product::lockForUpdate()
                        ->with('sell')
                        ->findOrFail($product->id);

                    if ($p->is_sold) {
                        return;
                    }

                    $sell = $p->sell;
                    if (!$sell) {
                        $sell = Sell::create([
                            'user_id'     => $p->user_id,
                            'product_id'  => $p->id,
                            'category_id' => $p->category_id ?? null,
                            'name'        => $p->name,
                            'brand'       => $p->brand ?? null,
                            'price'       => (int)$p->price,
                            'image_path'  => $p->image_path ?? null,
                            'condition'   => $p->condition ?? null,
                            'description' => $p->description ?? null,
                            'is_sold'     => false,
                        ]);
                    }

                    $purchase = Purchase::updateOrCreate(
                        [
                            'user_id' => Auth::id(),
                            'sell_id' => $sell->id,
                        ],
                        [
                            'amount'          => (int)$p->price,
                            'payment_method'  => $selectedMethod,
                            'purchased_at'    => now(),
                        ]
                    );

                    Payment::updateOrCreate(
                        [
                            'purchase_id' => $purchase->id,
                        ],
                        [
                            'payment_method'  => $selectedMethod,
                            'provider_txn_id' => 'convenience-store-reservation',
                            'paid_amount'     => (int)$p->price,
                            'paid_at'         => null,
                        ]
                    );

                    $p->update(['is_sold' => true]);
                    $sell->update(['is_sold' => true]);
                });

                return redirect()
                    ->route('payment.done')
                    ->with([
                        'status'          => 'コンビニ支払いの受付が完了しました。',
                        'payment_method'  => $selectedMethod,
                    ]);

            } catch (\Throwable $e) {
                Log::warning('Convenience finalize failed: ' . $e->getMessage());
                return redirect()
                    ->route('item')
                    ->with('error', 'コンビニ支払いの確定に失敗しました。');
            }
        }

        try {
            $publicKey = config('services.stripe.public') ?? env('STRIPE_PUBLIC_KEY');
            $secret    = config('services.stripe.secret') ?? env('STRIPE_SECRET_KEY');

            if (!$publicKey || !$secret) {
                return redirect()
                    ->route('item')
                    ->with('error', '決済設定が未構成です。');
            }

            Stripe::setApiKey($secret);

            $intent = PaymentIntent::create([
                'amount'   => (int) $product->price * 100,
                'currency' => 'jpy',
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'product_id' => (string)$product->id,
                    'user_id'    => (string)Auth::id(),
                ],
            ]);

            return view('payment.create', [
                'product'         => Product::with('sell')->find($product->id),
                'clientSecret'    => $intent->client_secret,
                'paymentIntent'   => $intent->id,
                'publicKey'       => $publicKey,
                'payment_method'  => $selectedMethod,
            ]);

        } catch (\Throwable $e) {
            Log::warning('Payment init failed: ' . $e->getMessage());
            return redirect()
                ->route('item')
                ->with('error', '決済初期化に失敗しました。');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'        => ['required','integer','exists:products,id'],
            'payment_intent_id' => ['required','string'],
            'payment_method'    => ['required','in:credit_card,convenience_store,bank_transfer'],
        ]);

        $selectedMethod = $validated['payment_method'];
        $stripeTxnId    = $validated['payment_intent_id'];
        $isLocalLike    = App::environment(['local','testing']);

        if (!$isLocalLike) {
            try {
                $pi = PaymentIntent::retrieve($validated['payment_intent_id']);
                $stripeTxnId = $pi->id;

                if (!in_array($pi->status, ['succeeded', 'requires_capture'], true)) {
                    return back()->with('error', '決済が未完了です（status: '.$pi->status.'）。');
                }
            } catch (\Throwable $e) {
                Log::warning('Stripe retrieve failed: ' . $e->getMessage());
                return back()->with('error', '決済確認に失敗しました。');
            }
        }

        $product = Product::with('sell')->findOrFail($validated['product_id']);

        if ($product->user_id === Auth::id() || $product->is_sold) {
            return redirect()
                ->route('item')
                ->with('error', 'この商品は購入できません。');
        }

        DB::transaction(function () use ($product, $selectedMethod, $stripeTxnId) {

            $p = Product::lockForUpdate()
                ->with('sell')
                ->findOrFail($product->id);

            if ($p->is_sold) {
                return;
            }

            $sell = $p->sell;
            if (!$sell) {
                $sell = Sell::create([
                    'user_id'     => $p->user_id,
                    'product_id'  => $p->id,
                    'category_id' => $p->category_id ?? null,
                    'name'        => $p->name,
                    'brand'       => $p->brand ?? null,
                    'price'       => (int)$p->price,
                    'image_path'  => $p->image_path ?? null,
                    'condition'   => $p->condition ?? null,
                    'description' => $p->description ?? null,
                    'is_sold'     => false,
                ]);
            }

            $purchase = Purchase::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'sell_id' => $sell->id,
                ],
                [
                    'amount'          => (int)$p->price,
                    'payment_method'  => $selectedMethod,
                    'purchased_at'    => now(),
                ]
            );

            Payment::updateOrCreate(
                [
                    'purchase_id' => $purchase->id,
                ],
                [
                    'payment_method'  => $selectedMethod,
                    'provider_txn_id' => $stripeTxnId,
                    'paid_amount'     => (int)$p->price,
                    'paid_at'         => now(),
                ]
            );

            $p->update(['is_sold' => true]);
            $sell->update(['is_sold' => true]);
        });

        return redirect()
            ->route('payment.done')
            ->with([
                'status'          => '決済が完了しました。',
                'payment_method'  => $selectedMethod,
            ]);
    }

    public function done()
    {
        if (view()->exists('payment.done')) {
            return view('payment.done');
        }

        return redirect()
            ->route('item')
            ->with('status', '購入が完了しました。');
    }
}
