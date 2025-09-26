<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;

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

        // 未ログインで mylist 指定時は all にフォールバック
        if ($tab === 'mylist' && !Auth::check()) {
            $tab = 'all';
        }

        if ($tab === 'mylist') {
            // 自分のお気に入り商品（favorites 経由）
            $products = Product::query()
                ->select('products.*')
                ->join('favorites', 'favorites.product_id', '=', 'products.id')
                ->where('favorites.user_id', Auth::id())
                ->orderByDesc('favorites.created_at')
                ->paginate($perPage)
                ->appends($request->query());
        } else {
            // おすすめ（全商品）— ログイン時は自分の出品を除外
            $query = Product::query()->latest('id');
            if (Auth::check()) {
                $query->where('user_id', '!=', Auth::id());
            }
            $products = $query->paginate($perPage)->appends($request->query());
            $tab = 'all';
        }

        return view('item', compact('products', 'tab'));
    }
}
