<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Sell;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class MypageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * マイページ
     * タブ：?page=sell | ?page=buy  ← 注意：Laravelのページャの page と競合するため、
     *                                 ページャ側は 'p1' / 'p2' を使う
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        abort_if(!$user, 403);

        // タブ値を正規化
        $raw = strtolower((string) $request->query('page', 'sell'));
        $tab = in_array($raw, ['sell', 'buy'], true) ? $raw : 'sell';

        $perPage = 24;

        // -------------------------------
        // 出品一覧（sells）← product/categories を同時読み込み
        // -------------------------------
        $mySells = Sell::query()
    ->where('user_id', $user->id)
    // ✖ 'categories' は存在しない → ○ Product 経由で category を読む
    ->with(['product.category'])
    ->latest('id')
    ->paginate($perPage, ['*'], 'p1')
    ->appends(['page' => 'sell']);
        // -------------------------------
        // 購入一覧（purchases → sells → products）
        //   ※ 現在の purchases スキーマ:
        //     - sell_id / amount / purchased_at
        // -------------------------------
        $purchasedProducts = Product::query()
    ->join('sells', 'sells.product_id', '=', 'products.id')
    ->join('purchases', 'purchases.sell_id', '=', 'sells.id')
    ->where('purchases.user_id', $user->id)
    ->orderByDesc('purchases.purchased_at')
    ->select([
        'products.id',
        'products.name',
        // 'products.image_url',  // ← これが無いので外す
        'products.image_path',   // ← こっちを使う
        'products.is_sold',
        'purchases.amount as purchased_amount',
        'purchases.purchased_at',
    ])
    ->paginate($perPage, ['*'], 'p2')
    ->appends(['page' => 'buy']);
        // Bladeで使うのは $page という変数名に（既存テンプレに合わせる）
        return view('mypage', [
            'user'               => $user,
            'page'               => $tab,               // 'sell' | 'buy'
            'mySells'            => $mySells,           // p1 ページャ
            'purchasedProducts'  => $purchasedProducts, // p2 ページャ
        ]);
    }
}
