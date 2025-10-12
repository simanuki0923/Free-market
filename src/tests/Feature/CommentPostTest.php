<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Comment;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

class CommentPostTest extends TestCase
{
    use RefreshDatabase;

    private function productShowUrl(Product $product): string
    {
        // 現状のルート定義に合わせる（/item/{item_id}, name: item.show）
        return Route::has('item.show')
            ? route('item.show', ['item_id' => $product->id])
            : '/item/'.$product->id;
    }

    private function productWithCategory(): Product
    {
        $category = Category::factory()->create(['name' => '家電']);
        return Product::factory()->create([
            'name'        => 'コメント対象商品',
            'brand'       => 'BrandZ',
            'price'       => 1200,
            'description' => '説明',
            'condition'   => '新品',
            'category_id' => $category->id,
        ]);
    }

    #[Test]
    public function ログイン済みユーザーはコメントを送信でき_保存され_コメント数が増える(): void
    {
        // --- 準備 ---
        $user    = User::factory()->create();
        $product = $this->productWithCategory();

        // 初期は0件
        $this->assertSame(0, Comment::where('product_id', $product->id)->count());

        // --- 手順：ログイン → コメント送信 ---
        $this->actingAs($user);

        $postUrl = route('comments.store', ['item_id' => $product->id]); // /item/{item_id}/comments
        $this->followingRedirects()
            ->post($postUrl, ['body' => 'テストコメントです'])
            ->assertOk();

        // --- 期待：DB保存＆件数増加 ---
        $this->assertDatabaseHas('comments', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'body'       => 'テストコメントです',
        ]);

        // 件数が1になっていること（画面反映も確認）
        $res  = $this->get($this->productShowUrl($product))->assertOk();
        $html = $res->getContent();

        // HTML: コメント（<span id="comments-total">1</span>件）
        $this->assertMatchesRegularExpression(
            '/コメント（\s*<span[^>]*id="comments-total"[^>]*>\s*1\s*<\/span>\s*件）/u',
            $html
        );
        // 本文が表示されている
        $res->assertSee('テストコメントです', false);
    }

    #[Test]
    public function ログインしていないユーザーはコメントを送信できない(): void
    {
        // --- 準備 ---
        $product = $this->productWithCategory();

        // --- 手順：未ログインでPOST ---
        $postUrl = route('comments.store', ['item_id' => $product->id]);
        $response = $this->post($postUrl, ['body' => '未ログイン投稿']);

        // --- 期待：ログインへリダイレクト & DBに保存されない ---
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('comments', [
            'product_id' => $product->id,
            'body'       => '未ログイン投稿',
        ]);
    }

    #[Test]
    public function コメント未入力ならバリデーションエラーが表示され保存されない(): void
    {
        // --- 準備 ---
        $user    = User::factory()->create();
        $product = $this->productWithCategory();

        // --- 手順：ログイン → 空文字で送信 ---
        $this->actingAs($user);
        $postUrl = route('comments.store', ['item_id' => $product->id]);
        $response = $this->post($postUrl, ['body' => '']);

        // --- 期待：エラー & 保存されない ---
        $response->assertSessionHasErrors(['body']);
        $this->assertDatabaseMissing('comments', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);
    }

    #[Test]
    public function コメントが255文字を超えるとバリデーションエラーが表示され保存されない(): void
    {
        // --- 準備 ---
        $user    = User::factory()->create();
        $product = $this->productWithCategory();

        // 256文字のダミー
        $tooLong = str_repeat('あ', 256);

        // --- 手順：ログイン → 256文字で送信 ---
        $this->actingAs($user);
        $postUrl = route('comments.store', ['item_id' => $product->id]);
        $response = $this->post($postUrl, ['body' => $tooLong]);

        // --- 期待：エラー & 保存されない ---
        $response->assertSessionHasErrors(['body']);
        $this->assertDatabaseMissing('comments', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'body'       => $tooLong,
        ]);
    }
}
