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
        $user    = User::factory()->create();
        $product = $this->productWithCategory();

        $this->assertSame(0, Comment::where('product_id', $product->id)->count());
        $this->actingAs($user);

        $postUrl = route('comments.store', ['item_id' => $product->id]);
        $this->followingRedirects()
            ->post($postUrl, ['body' => 'テストコメントです'])
            ->assertOk();

        $this->assertDatabaseHas('comments', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'body'       => 'テストコメントです',
        ]);

        $res  = $this->get($this->productShowUrl($product))->assertOk();
        $html = $res->getContent();

        $this->assertMatchesRegularExpression(
            '/コメント（\s*<span[^>]*id="comments-total"[^>]*>\s*1\s*<\/span>\s*件）/u',
            $html
        );
        $res->assertSee('テストコメントです', false);
    }

    #[Test]
    public function ログインしていないユーザーはコメントを送信できない(): void
    {
        $product = $this->productWithCategory();
        $postUrl = route('comments.store', ['item_id' => $product->id]);
        $response = $this->post($postUrl, ['body' => '未ログイン投稿']);

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
        $user    = User::factory()->create();
        $product = $this->productWithCategory();
        $this->actingAs($user);

        $postUrl = route('comments.store', ['item_id' => $product->id]);
        $response = $this->post($postUrl, ['body' => '']);
        $response->assertSessionHasErrors(['body']);
        $this->assertDatabaseMissing('comments', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);
    }

    #[Test]
    public function コメントが255文字を超えるとバリデーションエラーが表示され保存されない(): void
    {
        $user    = User::factory()->create();
        $product = $this->productWithCategory();
        $tooLong = str_repeat('あ', 256);

        $this->actingAs($user);
        $postUrl = route('comments.store', ['item_id' => $product->id]);
        $response = $this->post($postUrl, ['body' => $tooLong]);

        $response->assertSessionHasErrors(['body']);
        $this->assertDatabaseMissing('comments', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'body'       => $tooLong,
        ]);
    }
}
