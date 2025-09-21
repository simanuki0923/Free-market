<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;

class ItemListController extends Controller
{
    /**
     * マイリスト一覧（/?tab=mylist）
     * - 未ログイン時もリダイレクトせず、空一覧＋ログイン誘導を表示
     * - 検索 q、件数 per_page をサポート
     * - ページネーションで ?tab=mylist 等のクエリを保持
     */
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $perPage = max(1, min(60, (int) $request->integer('per_page', 20)));

        // ゲスト：空一覧を返して itemlist.blade.php を表示（タブは赤のまま）
        if (!Auth::check()) {
            return view('itemlist', [
                'tab'           => 'mylist',
                'isGuest'       => true,
                'products'      => collect(),
                'myFavoriteIds' => [],
            ]);
        }

        // ログイン時：favorites 経由でユーザーのお気に入り商品を取得
        $query = Product::query()
            ->join('favorites', 'favorites.product_id', '=', 'products.id')
            ->where('favorites.user_id', Auth::id())
            ->select('products.*')
            ->distinct()
            ->with(['categories:id,name'])
            ->withCount(['favoredByUsers as favorites_count', 'comments'])
            ->latest('products.id');

        if ($keyword !== '') {
            $query->where('products.name', 'LIKE', "%{$keyword}%");
        }

        $products = $query->paginate($perPage)->withQueryString();

        $myFavoriteIds = Product::query()
            ->join('favorites', 'favorites.product_id', '=', 'products.id')
            ->where('favorites.user_id', Auth::id())
            ->pluck('products.id')
            ->all();

        return view('itemlist', [
            'tab'           => 'mylist',
            'isGuest'       => false,
            'products'      => $products,
            'myFavoriteIds' => $myFavoriteIds,
        ]);
    }
}
