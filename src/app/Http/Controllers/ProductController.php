<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /** 商品詳細 */
    public function show(int $item_id)
    {
        $product = Product::with([
                'category',
                'sell',
                'comments.user.profile',
                // 'favorites' は withCount で数だけ取れば十分なので省略可
                // 'favoredByUsers' は一覧不要なら省略可（必要なら残してOK）
            ])
            ->withCount(['favorites','comments'])
            ->findOrFail($item_id);

        // ログインユーザーがお気に入り済みか（UIでハート色分け等に使用）
        $isFavorited = Auth::check()
            ? $product->favorites()->where('user_id', Auth::id())->exists()
            : false;

        return view('product', compact('product', 'isFavorited'));
    }
}
