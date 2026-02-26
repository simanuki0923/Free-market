<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sell;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentController extends Controller
{
    private const PAYMENT_METHOD_CREDIT_CARD = 'credit_card';
    private const PAYMENT_METHOD_CONVENIENCE_STORE = 'convenience_store';
    private const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';

    private const TRANSACTION_STATUS_ONGOING = 'ongoing';

    public function __construct()
    {
        $this->middleware('auth');

        if (App::environment('testing')) {
            return;
        }

        $secret = config('services.stripe.secret') ?? env('STRIPE_SECRET_KEY');

        if (!empty($secret)) {
            Stripe::setApiKey($secret);
        }
    }

    public function create(Request $request): View|RedirectResponse
    {
        $productId = $request->input('productId') ?? $request->input('item_id');
        $selectedMethod = (string) $request->input('payment_method', '');

        abort_unless($productId, 404, '商品が指定されていません。');

        $product = Product::query()->findOrFail((int) $productId);

        $invalidPurchaseTarget = ($product->user_id === Auth::id()) || (bool) $product->is_sold;
        if ($invalidPurchaseTarget) {
            return $this->redirectToItemWithError('購入できない商品です。');
        }

        if ($selectedMethod === '') {
            return $this->redirectToItemWithError('支払い方法が選択されていません。');
        }

        if (App::environment('testing')) {
            return $this->handleTestingPayment($product, $selectedMethod);
        }

        if ($selectedMethod === self::PAYMENT_METHOD_CONVENIENCE_STORE) {
            return $this->handleConvenienceStorePayment($product, $selectedMethod);
        }

        return $this->showStripePaymentPage($product, $selectedMethod);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'payment_intent_id' => ['required', 'string'],
            'payment_method' => [
                'required',
                'in:' . implode(',', [
                    self::PAYMENT_METHOD_CREDIT_CARD,
                    self::PAYMENT_METHOD_CONVENIENCE_STORE,
                    self::PAYMENT_METHOD_BANK_TRANSFER,
                ]),
            ],
        ]);

        $selectedMethod = $validated['payment_method'];
        $stripeTransactionId = $validated['payment_intent_id'];
        $isLocalLike = App::environment(['local', 'testing']);

        if (!$isLocalLike) {
            $stripeResult = $this->verifyStripePaymentIntent($validated['payment_intent_id']);

            if ($stripeResult instanceof RedirectResponse) {
                return $stripeResult;
            }

            $stripeTransactionId = $stripeResult;
        }

        $product = Product::with('sell')->findOrFail((int) $validated['product_id']);

        $invalidPurchaseTarget = ($product->user_id === Auth::id()) || (bool) $product->is_sold;
        if ($invalidPurchaseTarget) {
            return $this->redirectToItemWithError('この商品は購入できません。');
        }

        $this->finalizePurchase($product, $selectedMethod, $stripeTransactionId, now());

        return redirect()
            ->route('payment.done')
            ->with([
                'status' => '決済が完了しました。',
                'payment_method' => $selectedMethod,
            ]);
    }

    public function done(): View|RedirectResponse
    {
        if (view()->exists('payment.done')) {
            return view('payment.done');
        }

        return redirect()
            ->route('item')
            ->with('status', '購入が完了しました。');
    }

    private function handleTestingPayment(Product $product, string $selectedMethod): RedirectResponse
    {
        try {
            $this->finalizePurchase($product, $selectedMethod, 'test-intent', now());

            return redirect()
                ->route('payment.done')
                ->with([
                    'status' => 'テスト決済が完了しました。',
                    'payment_method' => $selectedMethod,
                ]);
        } catch (\Throwable $throwable) {
            Log::warning('Test finalize failed: ' . $throwable->getMessage());

            return $this->redirectToItemWithError('テスト決済処理に失敗しました。');
        }
    }

    private function handleConvenienceStorePayment(Product $product, string $selectedMethod): RedirectResponse
    {
        try {
            $this->finalizePurchase(
                $product,
                $selectedMethod,
                'convenience-store-reservation',
                null
            );

            return redirect()
                ->route('payment.done')
                ->with([
                    'status' => 'コンビニ支払いの受付が完了しました。',
                    'payment_method' => $selectedMethod,
                ]);
        } catch (\Throwable $throwable) {
            Log::warning('Convenience finalize failed: ' . $throwable->getMessage());

            return $this->redirectToItemWithError('コンビニ支払いの確定に失敗しました。');
        }
    }

    private function showStripePaymentPage(Product $product, string $selectedMethod): View|RedirectResponse
    {
        try {
            $publicKey = config('services.stripe.public') ?? env('STRIPE_PUBLIC_KEY');
            $secret = config('services.stripe.secret') ?? env('STRIPE_SECRET_KEY');

            if (empty($publicKey) || empty($secret)) {
                return $this->redirectToItemWithError('決済設定が未設定です。');
            }

            Stripe::setApiKey($secret);

            $intent = PaymentIntent::create([
                'amount' => (int) $product->price * 100,
                'currency' => 'jpy',
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'product_id' => (string) $product->id,
                    'user_id' => (string) Auth::id(),
                ],
            ]);

            return view('payment.create', [
                'product' => Product::with('sell')->find($product->id),
                'clientSecret' => $intent->client_secret,
                'paymentIntent' => $intent->id,
                'publicKey' => $publicKey,
                'payment_method' => $selectedMethod,
            ]);
        } catch (\Throwable $throwable) {
            Log::warning('Payment init failed: ' . $throwable->getMessage());

            return $this->redirectToItemWithError('決済初期化に失敗しました。');
        }
    }

    private function verifyStripePaymentIntent(string $paymentIntentId): string|RedirectResponse
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            if (!in_array($paymentIntent->status, ['succeeded', 'requires_capture'], true)) {
                return back()->with('error', '決済が未完了です。（status: ' . $paymentIntent->status . '）');
            }

            return $paymentIntent->id;
        } catch (\Throwable $throwable) {
            Log::warning('Stripe retrieve failed: ' . $throwable->getMessage());

            return back()->with('error', '決済確認に失敗しました。');
        }
    }

    private function finalizePurchase(
        Product $product,
        string $paymentMethod,
        string $providerTransactionId,
        ?\DateTimeInterface $paidAt
    ): void {
        DB::transaction(function () use ($product, $paymentMethod, $providerTransactionId, $paidAt): void {
            $lockedProduct = Product::lockForUpdate()
                ->with('sell')
                ->findOrFail($product->id);

            if ((bool) $lockedProduct->is_sold) {
                return;
            }

            $sell = $this->ensureSellSnapshot($lockedProduct);

            $purchase = Purchase::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'sell_id' => $sell->id,
                ],
                [
                    'amount' => (int) $lockedProduct->price,
                    'payment_method' => $paymentMethod,
                    'purchased_at' => now(),
                ]
            );

            Payment::updateOrCreate(
                [
                    'purchase_id' => $purchase->id,
                ],
                [
                    'payment_method' => $paymentMethod,
                    'provider_txn_id' => $providerTransactionId,
                    'paid_amount' => (int) $lockedProduct->price,
                    'paid_at' => $paidAt,
                ]
            );

            $this->upsertTransaction($purchase, $sell, $lockedProduct);

            $lockedProduct->update(['is_sold' => true]);
            $sell->update(['is_sold' => true]);
        });
    }

    private function ensureSellSnapshot(Product $product): Sell
    {
        $sell = $product->sell;

        if ($sell instanceof Sell) {
            return $sell;
        }

        return Sell::create([
            'user_id' => $product->user_id,
            'product_id' => $product->id,
            'category_id' => $product->category_id ?? null,
            'name' => $product->name,
            'brand' => $product->brand ?? null,
            'price' => (int) $product->price,
            'image_path' => $product->image_path ?? null,
            'condition' => $product->condition ?? null,
            'description' => $product->description ?? null,
            'is_sold' => false,
        ]);
    }

    private function upsertTransaction(Purchase $purchase, Sell $sell, Product $product): void
    {
        Transaction::updateOrCreate(
            [
                'purchase_id' => $purchase->id,
            ],
            [
                'sell_id' => $sell->id,
                'product_id' => $product->id,
                'seller_id' => $sell->user_id,
                'buyer_id' => $purchase->user_id,
                'status' => self::TRANSACTION_STATUS_ONGOING,
                'last_message_at' => now(),
            ]
        );
    }

    private function redirectToItemWithError(string $message): RedirectResponse
    {
        return redirect()
            ->route('item')
            ->with('error', $message);
    }
}