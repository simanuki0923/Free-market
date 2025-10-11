<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * 商品検索
     *
     * 受け口は search を正としつつ、既存互換で q もフォールバック。
     * 画面表示は常に search を用いて統一します。
     */
    public function index(Request $request)
    {
        // パラメータ統一（互換：q → search へフォールバック）
        $search = $request->string('search', $request->string('q'))->toString();

        $query = Product::query();

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'LIKE', $like)
                  ->orWhere('brand', 'LIKE', $like)
                  ->orWhere('description', 'LIKE', $like);
            });
        }

        // ページネーションは常に現在のクエリを引き継ぐ
        $products = $query->latest()->paginate(20)->appends($request->query());

        return view('search', [
            'products' => $products,
            'search'   => $search,   // Blade 側で入力値・表示に使用
        ]);
    }
}
