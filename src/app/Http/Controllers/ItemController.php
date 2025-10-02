<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

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

        // 1) タブ値を正規化（未知の値は all にフォールバック）
        $rawTab = strtolower((string)$request->query('tab', 'all'));
        $tab = in_array($rawTab, ['all', 'mylist'], true) ? $rawTab : 'all';

        $page = (int) $request->query('page', 1);
        $isLoggedIn = Auth::check();

        if ($tab === 'mylist') {
            if (!$isLoggedIn) {
                // 未ログイン：空のページネータを返す（tab は mylist のまま）
                $products = new LengthAwarePaginator(
                    collect(), // 空
                    0,         // トータル0件
                    $perPage,
                    $page,
                    [
                        'path'  => url()->current(),
                        'query' => array_merge($request->query(), ['tab' => 'mylist']),
                    ]
                );
                $products->setPageName('page');
            } else {
                // ログイン済：お気に入り一覧（新しい順）
                $products = Product::query()
                    ->select('products.*')
                    ->join('favorites', 'favorites.product_id', '=', 'products.id')
                    ->where('favorites.user_id', Auth::id())
                    ->orderByDesc('favorites.created_at')
                    ->paginate($perPage)
                    ->appends(array_merge($request->query(), ['tab' => 'mylist']));
            }
        } else {
            // おすすめ：全商品（ログイン中は自分の出品を除外）
            $query = Product::query()->latest('id');
            if ($isLoggedIn) {
                $query->where('user_id', '!=', Auth::id());
            }

            $products = $query
                ->paginate($perPage)
                ->appends(array_merge($request->query(), ['tab' => 'all']));
            $tab = 'all'; // 念のため固定
        }

        return view('item', [
            'products'     => $products,
            'tab'          => $tab,        // ← これを Blade の表示状態に使う
            'isLoggedIn'   => $isLoggedIn, // ← Blade 分岐用
        ]);
    }
}
