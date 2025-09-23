<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;

class ItemListController extends Controller
{
    /**
     * マイリスト（お気に入り）
     * 要件:
     *  - 未認証は「何も表示しない」（文言も出さない）
     *  - 認証済みは「お気に入りした商品」を表示
     *  - 売却済みは blade 側で "Sold" バッジ表示（$p->is_sold）
     */
    public function index(Request $request)
    {
        $tab = 'mylist';

        // 未認証は空で返して blade 側で完全非表示
        if (!Auth::check()) {
            $isGuest  = true;
            $products = collect(); // 空コレクション
            return view('itemlist', compact('products', 'isGuest', 'tab'));
        }

        $perPage = 24;
        $userId  = Auth::id();

        // Product モデルに favoredByUsers()（favorites 中間テーブル）定義前提
        $products = Product::query()
            ->whereHas('favoredByUsers', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })
            // 必要に応じて ->with([...]) を追加
            ->latest('id')
            ->paginate($perPage)
            ->appends($request->query());

        $isGuest = false;

        return view('itemlist', compact('products', 'isGuest', 'tab'));
    }
}
