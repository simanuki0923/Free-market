<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
    public function show(int $item_id): View
    {
        // 一括で関連と件数をロード
        $product = Product::with([
                'categories:id,name',
                'comments.user.profile',  // コメント + 投稿ユーザーのプロフィール
            ])
            ->withCount([
                'favoredByUsers as favorites_count', // Product::favoredByUsers() が必要
                'comments',                           // コメント件数
            ])
            ->findOrFail($item_id);

        $isFavorited = $this->checkIfFavorited($product);

        return view('product', [
            'product'       => $product,
            'isFavorited'   => $isFavorited,
            'commentsCount' => $product->comments_count, // Blade側で件数表示に利用可
        ]);
    }

    /**
     * コメント保存（ログイン必須）
     *
     * ルート例:
     * Route::post('/item/{item_id}/comments', [ProductController::class, 'storeComment'])
     *     ->whereNumber('item_id')
     *     ->name('comments.store');
     */
    public function storeComment(Request $request, int $item_id): RedirectResponse
    {
        // バリデーション（255文字制限などは要件に合わせて調整）
        $validated = $request->validate(
            ['body' => ['required', 'string', 'max:255']],
            [
                'body.required' => 'コメントを入力してください。',
                'body.max'      => 'コメントは255文字以内で入力してください。',
            ]
        );

        // 対象商品が存在するか確認
        $product = Product::findOrFail($item_id);

        // 保存
        $product->comments()->create([
            'user_id'   => $request->user()->id,
            'body'      => $validated['body'],
        ]);

        // 元の詳細画面に戻る（withCount で件数が更新される）
        return back()->with('status', 'コメントを投稿しました。');
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
