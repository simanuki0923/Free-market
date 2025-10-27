<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Purchase;
use App\Http\Requests\PurchaseRequest;

class PurchaseController extends Controller
{
    public function show(int $item_id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $product = Product::with('sell')->findOrFail($item_id);

        if (
            $product->user_id === Auth::id() ||
            $product->is_sold ||
            optional($product->sell)->is_sold
        ) {
            return redirect()
                ->route('item')
                ->with('error', '購入できない商品です。');
        }

        $profile = Profile::where('user_id', Auth::id())->first();

        $payment = (object)['payment_method' => null];

        return view('purchase', compact('product', 'profile', 'payment'));
    }

    public function payment(PurchaseRequest $request)
    {
        $validated = $request->validated();

        if (empty($validated['postal_code']) || empty($validated['address1'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'address1' => '配送先が未設定です。「変更する」から登録してください。',
                ]);
        }

        $product = Product::with('sell')->findOrFail($request->input('item_id'));

        if (
            $product->user_id === Auth::id() ||
            $product->is_sold ||
            optional($product->sell)->is_sold
        ) {
            return redirect()
                ->route('item')
                ->with('error', '購入できない商品です。');
        }

        $purchase = Purchase::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'sell_id' => optional($product->sell)->id,
            ],
            [
                'amount'          => (int) $product->price,
                'payment_method'  => $validated['payment_method'],
            ]
        );

        return redirect()->route('payment.create', [
            'item_id'        => $product->id,
            'payment_method' => $validated['payment_method'],
            'purchase_id'    => $purchase->id,
        ])->with('status', '購入情報を保存しました。続けてお支払い手続きへ進んでください。');
    }
}
