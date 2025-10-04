<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Profile;
use App\Http\Requests\PurchaseRequest;

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
        $payment = (object)['payment_method' => null];

        return view('purchase', compact('product', 'profile', 'payment'));
    }

    /**
     * バリデーション実行 → 決済処理へ受け渡し
     * GET /purchase/payment?item_id=...
     * ルート例：Route::get('/purchase/payment', [PurchaseController::class, 'payment'])->name('purchase.payment');
     */
    public function payment(PurchaseRequest $request)
    {
        $validated = $request->validated();

        // 配送先が未設定なら戻す（追加の安全策）
        if (empty($validated['postal_code']) || empty($validated['address1'])) {
            return back()->withInput()->withErrors(['address1' => '配送先が未設定です。「変更する」から登録してください。']);
        }

        // === 以降：既存の決済フローにハンドオフ ===
        // もし既存の PaymentController@create に渡すなら、クエリを引き継いでリダイレクト
        // 例）
        return redirect()->route('payment.create', [
            'item_id'        => $request->input('item_id'),
            'payment_method' => $validated['payment_method'],
        ])->with('status', '入力を確認しました。');

        // もしくはここで直接 Stripe 画面へ遷移/生成 など、既存の仕様に合わせて変更してください。
    }
}
