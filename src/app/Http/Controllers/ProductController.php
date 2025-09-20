<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /** 商品詳細（未ログインOK） */
    public function show(Product $product)
    {
        // 多対多 categories をロード。コメントのユーザー＆プロフィールも合わせて。
        $product->load([
            'categories:id,name',
            'comments.user.profile',
        ])->loadCount([
            'favoredByUsers as favorites_count', // ← このリレーションが未定義なら後述参照
            'comments',
        ]);

        $isFavorited = false;
        if (Auth::check() && method_exists($product, 'favoredByUsers')) {
            $isFavorited = $product->favoredByUsers()
                ->where('user_id', Auth::id())
                ->exists();
        }

        return view('product', compact('product', 'isFavorited'));
    }
}
