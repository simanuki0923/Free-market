<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        // 入力のバリデーション（任意のフィルタにも対応）
        $validated = $request->validate([
            'search'    => ['nullable', 'string', 'max:200'],
            'category'  => ['nullable', 'integer'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'hide_sold' => ['nullable', 'boolean'],
            'sort'      => ['nullable', 'in:newest,price_asc,price_desc'],
        ]);

        // 検索キーワードをトークン化（全角スペース→半角・前後空白除去）
        $raw     = (string)($validated['search'] ?? '');
        $keyword = trim(mb_convert_kana($raw, 's'));
        $tokens  = collect(preg_split('/\s+/u', $keyword, -1, PREG_SPLIT_NO_EMPTY));

        // フィルタ指定の有無
        $hasFilter =
            $request->filled('category') ||
            $request->filled('min_price') ||
            $request->filled('max_price') ||
            $request->boolean('hide_sold');

        // 何も指定が無ければ一覧へリダイレクト（好みで「全件表示」にしてもOK）
        if ($tokens->isEmpty() && !$hasFilter) {
            return redirect()->route('products.index'); // 既存の一覧ルートに揃えてください
        }

        $query = Product::query();

        // 自分の出品を除外（必要なければ削除）
        if (Auth::check()) {
            $query->where('user_id', '!=', Auth::id());
        }

        // キーワード AND 検索（各トークンで name/brand/description を OR）
        if ($tokens->isNotEmpty()) {
            $query->where(function ($q) use ($tokens) {
                foreach ($tokens as $t) {
                    $like = '%' . $t . '%';
                    $q->where(function ($q2) use ($like) {
                        $q2->orWhere('name', 'like', $like)
                           ->orWhere('brand', 'like', $like)
                           ->orWhere('description', 'like', $like);
                    });
                }
            });
        }

        // ===== 任意の追加フィルタ =====
        if ($request->filled('category')) {
            $query->where('category_id', (int)$request->input('category'));
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (int)$request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (int)$request->input('max_price'));
        }
        if ($request->boolean('hide_sold')) {
            $query->where('is_sold', false);
        }

        // ===== 並び替え =====
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
            default:
                $query->latest('id'); // created_at でもOK
                break;
        }

        $products = $query
            ->select(['id', 'name', 'price', 'image_url', 'is_sold']) // 必要に応じて列を追加
            ->paginate(24)
            ->withQueryString(); // ページ送りで検索条件を保持

        return view('search.index', [
            'products' => $products,
            'keyword'  => $keyword,
        ]);
    }
}
