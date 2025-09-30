<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Http\Requests\CommentRequest;

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

    public function storeComment(CommentRequest $request, int $item_id): RedirectResponse
    {
        $validated = $request->validated(); // ['body' => '...']

        $product = Product::findOrFail($item_id);

        $product->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $validated['body'],
        ]);

        return back()->with('status', 'コメントを投稿しました。');
    }

    // ...（他は現状のまま）...


    /**
     * ログインユーザーが「お気に入り済み」かを判定
     */
    private function checkIfFavorited(Product $product): bool
    {
        if (!Auth::check()) {
            return false;
        }

        // リレーションが未定義でも落ちないよう存在チェック
        if (!method_exists($product, 'favoredByUsers')) {
            return false;
        }

        return $product->favoredByUsers()
            ->where('user_id', Auth::id())
            ->exists();
    }


}
