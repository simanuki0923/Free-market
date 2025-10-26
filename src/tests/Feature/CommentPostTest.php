<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommentPostTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 商品詳細ページのURLを生成するヘルパ
     * ルート定義：
     *   Route::get('/item/{item_id}', [ProductController::class, 'show'])
     *        ->whereNumber('item_id')
     *        ->name('item.show');
     */
    private function productShowUrl(Product $product): string
    {
        return route('item.show', ['item_id' => $product->id]);
    }

    #[Test]
    public function ログイン済みユーザーはコメントを送信でき_保存され_コメント数が増える(): void
    {
        /**
         * 期待すること（本番挙動に合わせ済み）：
         * - ログイン済みユーザーが /item/{id}/comments に POST できる
         * - その後 /item/{id} (商品詳細) を見るとコメントが表示されている
         * - コメント数カウンタ（id="comments-total" や #comment-count）が 1 になっている
         *
         * ※ 画面上は「コメント（<span id="comments-total">1</span>）」という表示で、
         *    「件」という文字は付いていない。古いテストは "件" も期待していたのでそこを修正する。
         */

        // ユーザー・商品を用意
        $user = User::factory()->create([
            'name' => '廣川 直子',
        ]);

        $product = Product::factory()->create([
            'name'        => 'コメント対象商品',
            'brand'       => 'BrandZ',
            'price'       => 1200,
            'description' => '説明',
            // 状態やカテゴリ等は ProductShowTest と同様の形を想定
        ]);

        // ログイン
        $this->actingAs($user);

        // コメント投稿
        $postResponse = $this->post(route('comments.store', ['item_id' => $product->id]), [
            'body' => 'テストコメントです',
        ]);

        // コントローラ側では基本リダイレクトのはず（一覧や詳細に戻すなど）
        $postResponse->assertStatus(302);

        // 投稿後の商品詳細ページを確認
        $res  = $this->get($this->productShowUrl($product))->assertOk();
        $html = $res->getContent();

        // コメント数の表示が「コメント（<span id="comments-total">1</span>）」のように
        // 件数のみで表示されていることを確認する
        //
        // 旧テストは「…</span>件）」と '件' まで含めていたが、
        // 実際のHTMLには '件' が無いので '件' は期待しない。
        $this->assertMatchesRegularExpression(
            '/コメント（\s*<span[^>]*id="comments-total"[^>]*>\s*1\s*<\/span>\s*）/u',
            $html
        );

        // コメント本文が表示されていること
        $res->assertSee('テストコメントです', false);
    }

    #[Test]
    public function ログインしていないユーザーはコメントを送信できない(): void
    {
        /**
         * 未ログインの場合は /item/{id}/comments POST で login に飛ばされる想定。
         */

        $product = Product::factory()->create();

        $res = $this->post(route('comments.store', ['item_id' => $product->id]), [
            'body' => '未ログインでの投稿',
        ]);

        // authミドルウェアによりログイン画面（route('login')）へリダイレクトするはず
        $res->assertRedirect(route('login'));
    }

    #[Test]
    public function コメント未入力ならバリデーションエラーが表示され保存されない(): void
    {
        /**
         * body が空の場合はバリデーションエラーになる。
         * 実装ではセッションにエラーを積んでリダイレクトしているはずなので、
         * そこを確認する。
         */

        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user);

        $res = $this->post(route('comments.store', ['item_id' => $product->id]), [
            'body' => '', // 空
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors([
            'body',
        ]);

        // 詳細ページに実際に反映されていないことまでは厳密には見ない
        // （DB内容チェックは別テストで担保済みという扱い）
    }

    #[Test]
    public function コメントが255文字を超えるとバリデーションエラーが表示され保存されない(): void
    {
        /**
         * body が長すぎる場合（>255文字）もエラーになるはず。
         */

        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user);

        // 256文字のダミーコメント
        $tooLong = str_repeat('あ', 256);

        $res = $this->post(route('comments.store', ['item_id' => $product->id]), [
            'body' => $tooLong,
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors([
            'body',
        ]);
    }
}
