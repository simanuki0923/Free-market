<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * 商品詳細（未ログインOK）
     *
     * ルート例:
     * Route::get('/item/{item_id}', [ProductController::class, 'show'])
     *     ->whereNumber('item_id')
     *     ->name('item.show');
     *
     * @param  int  $item_id  商品の数値ID
     */
    public function show(int $item_id)
    {
        // 一括で関連と件数をロード
        $product = Product::with([
                'categories:id,name',
                'comments.user.profile',
            ])
            ->withCount([
                'favoredByUsers as favorites_count', // Product::favoredByUsers() が必要
                'comments',
            ])
            ->findOrFail($item_id);

        $isFavorited = $this->checkIfFavorited($product);

        return view('product', compact('product', 'isFavorited'));
    }

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
