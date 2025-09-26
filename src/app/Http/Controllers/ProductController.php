<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(int $item_id)
    {
        $product = Product::query()
            ->with([
                'sell',                  // 画像フォールバック用（sells.product_id = products.id）
                'categories',            // 使っていれば
                'comments.user.profile', // 使っていれば
            ])
            // 未定義の favorites を withCount に入れない
            ->withCount([
                // 'favorites as favorites_count', ← 未定義なら入れない
                'comments as comments_count',
            ])
            ->findOrFail($item_id);

        // ★ お気に入り数（存在する関係だけ使う）
        // 例: users との多対多が favoredByUsers() で定義されている前提
        $favoritesCount = 0;
        if (method_exists($product, 'favoredByUsers')) {
            $favoritesCount = $product->favoredByUsers()->count();
        }

        // ★ お気に入り済みか
        $isFavorited = false;
        if (auth()->check() && method_exists($product, 'favoredByUsers')) {
            $isFavorited = $product->favoredByUsers()
                ->where('user_id', auth()->id())
                ->exists();
        }

        return view('product', [
            'product'       => $product,
            'isFavorited'   => $isFavorited,
            'commentsCount' => (int) ($product->comments_count ?? 0),
            'favoritesCount'=> $favoritesCount, // ← Blade で使えるように渡す
        ]);
    }
}
