<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MypageController extends Controller
{
    /**
     * 一覧表示（トップ / マイページ）
     *
     * 機能:
     * - タブ切替: ?tab=all | mylist
     * - キーワード検索: ?q=文字列 （商品名 LIKE）
     * - 並び順: 販売中を先に、新着順
     * - ページネーション: ?page=2 等
     */
    public function index(Request $request)
    {
        // --- 入力取得（デフォルト: all）------------------------------------
        $tab      = strtolower($request->query('tab', 'all'));  // 'all' or 'mylist'
        $keyword  = trim((string) $request->query('q', ''));
        $perPage  = (int) $request->integer('per_page', 12);    // 1ページ件数

        // --- ベースクエリ ---------------------------------------------------
        $query = Product::query()
            ->select(['products.id', 'products.user_id', 'products.name', 'products.image_url', 'products.is_sold', 'products.created_at'])
            ->orderBy('products.is_sold')   // 販売中(false) -> 売却済み(true)
            ->latest('products.id');        // 新着順

        // --- 検索（商品名 LIKE）--------------------------------------------
        if ($keyword !== '') {
            $query->where('products.name', 'LIKE', "%{$keyword}%");
        }

        // --- タブ: mylist（お気に入り）--------------------------------------
        if ($tab === 'mylist' && Auth::check()) {
            // 【推奨】favorites(user_id, product_id) がある場合の実装
            $query->join('favorites', 'favorites.product_id', '=', 'products.id')
                  ->where('favorites.user_id', Auth::id())
                  ->select('products.*')    // join したので明示的に products.* に戻す
                  ->distinct();

            // ★もし「マイリスト＝自分の出品」を見せたい仕様なら、上の join を削除して代わりにこちら1行:
            // $query->where('products.user_id', Auth::id());
        }

        // --- ページネーション & クエリパラメータ引継ぎ ---------------------
        $products = $query->paginate($perPage)->withQueryString();

        // --- 画面へ ---------------------------------------------------------
        return view('mypage', [
            'products' => $products,
            'tab'      => $tab,
            'q'        => $keyword,
        ]);
    }
}
