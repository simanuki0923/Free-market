<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;

class ItemController extends Controller
{
    /**
     * おすすめ一覧（未ログインOK）
     * 要件:
     *  - ログイン時は「自分の出品」を除外
     *  - 売却済みは blade 側で "Sold" バッジ表示（$p->is_sold）
     */
    public function index(Request $request)
    {
        $perPage = 24;

        $query = Product::query()
            // 必要に応じて ->with([...]) を追加（カテゴリやコメント件数など）
            ->latest('id');

        // ログイン中のみ「自分の出品」を除外
        if (Auth::check()) {
            $query->where('user_id', '!=', Auth::id());
        }

        $products = $query->paginate($perPage)->appends($request->query());

        // blade のタブ表示用
        $tab = 'all';

        return view('item', compact('products', 'tab'));
    }
}
