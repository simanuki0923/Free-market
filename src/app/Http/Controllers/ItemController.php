<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class ItemController extends Controller
{
    /**
     * おすすめ（全商品一覧）
     * - 未ログインでも表示可
     * - ?tab= をそのまま Blade に渡す（tab=mylist でも全商品を出す）
     * - 検索 q、件数 per_page をサポート
     * - ページネーションでクエリ維持
     */
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $perPage = max(1, min(60, (int) $request->integer('per_page', 20)));
        $tab     = strtolower($request->query('tab', 'all')); // ← Blade のタブ表示に使う

        $query = Product::query()
            ->with(['categories:id,name'])
            ->withCount(['favoredByUsers as favorites_count', 'comments'])
            ->latest('products.id');

        if ($keyword !== '') {
            $query->where('products.name', 'LIKE', "%{$keyword}%");
        }

        $products = $query->paginate($perPage)->withQueryString();

        // リレーション未定義でも安全に動かすため、JOINではなくDBクエリで取得
        $myFavoriteIds = Auth::check()
            ? DB::table('favorites')
                ->where('user_id', Auth::id())
                ->pluck('product_id')
                ->all()
            : [];

        return view('item', [
            'tab'           => $tab,       // 'all' でも 'mylist' でもそのまま渡す（見た目だけ切替）
            'products'      => $products,  // 中身は常に全商品
            'myFavoriteIds' => $myFavoriteIds,
        ]);
    }
}
