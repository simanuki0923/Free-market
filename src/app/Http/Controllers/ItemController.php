<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator; // ← 追加
use Illuminate\Support\Collection;              // ← 追加

class ItemController extends Controller
{
    /**
     * トップ一覧
     * - /?tab=all    : おすすめ（全商品）
     * - /?tab=mylist : 自分のお気に入り（favorites）
     */
    public function index(Request $request)
    {
        $perPage = 24;
        $tab = strtolower($request->query('tab', 'all'));
        $page = (int) $request->query('page', 1);

        $canSeeMylist = Auth::check();

        if ($tab === 'mylist') {
            if (!$canSeeMylist) {
                // 未ログイン時：空のページネータを返し、tab は mylist のまま
                $products = new LengthAwarePaginator(
                    collect(),     // 空コレクション
                    0,             // トータル0件
                    $perPage,
                    $page,
                    [
                        'path'  => url()->current(),
                        'query' => $request->query(),
                    ]
                );
            } else {
                // 自分のお気に入り商品（favorites 経由）
                $products = Product::query()
                    ->select('products.*')
                    ->join('favorites', 'favorites.product_id', '=', 'products.id')
                    ->where('favorites.user_id', Auth::id())
                    ->orderByDesc('favorites.created_at')
                    ->paginate($perPage)
                    ->appends($request->query());
            }
        } else {
            // おすすめ（全商品）— ログイン時は自分の出品を除外
            $query = Product::query()->latest('id');
            if (Auth::check()) {
                $query->where('user_id', '!=', Auth::id());
            }
            $products = $query->paginate($perPage)->appends($request->query());
            $tab = 'all';
        }

        return view('item', [
            'products'     => $products,
            'tab'          => $tab,
            'canSeeMylist' => $canSeeMylist, // ← Blade で分岐に使う
        ]);
    }
}
