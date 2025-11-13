<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 24;

        // タブ判定（all / mylist）
        $rawTab = strtolower((string) $request->query('tab', 'all'));
        $tab    = in_array($rawTab, ['all', 'mylist'], true) ? $rawTab : 'all';

        $page       = (int) $request->query('page', 1);
        $isLoggedIn = Auth::check();

        // ★ 検索キーワード（ヘッダーの input name="keyword"）
        $keyword = trim((string) $request->input('keyword', ''));

        if ($tab === 'mylist') {
            // ========= マイリストタブ =========
            if (!$isLoggedIn) {
                // 未ログイン：空のページネーションを返す
                $products = new LengthAwarePaginator(
                    collect(),
                    0,
                    $perPage,
                    $page,
                    [
                        'path'  => url()->current(),
                        'query' => array_merge($request->query(), ['tab' => 'mylist']),
                    ]
                );
                $products->setPageName('page');
            } else {
                // お気に入り + キーワード部分一致検索
                $products = Product::query()
                    ->select('products.*')
                    ->join('favorites', 'favorites.product_id', '=', 'products.id')
                    ->where('favorites.user_id', Auth::id())
                    // ★ ここで「自分が出品した商品」を除外
                    ->where('products.user_id', '!=', Auth::id())
                    // ★ キーワード検索スコープ
                    ->keywordSearch($keyword)
                    ->orderByDesc('favorites.created_at')
                    ->paginate($perPage)
                    ->appends(array_merge($request->query(), ['tab' => 'mylist']));
            }
        } else {
            // ========= おすすめタブ =========
            $query = Product::query()->latest('id');

            // ログイン中は自分の商品を除外
            if ($isLoggedIn) {
                $query->where('user_id', '!=', Auth::id());
            }

            // ★ キーワード部分一致検索
            $query->keywordSearch($keyword);

            $products = $query
                ->paginate($perPage)
                ->appends(array_merge($request->query(), ['tab' => 'all']));

            $tab = 'all';
        }

        return view('item', [
            'products'   => $products,
            'tab'        => $tab,
            'isLoggedIn' => $isLoggedIn,
            'keyword'    => $keyword,
        ]);
    }
}
