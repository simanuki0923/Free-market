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

    private function productShowUrl(Product $product): string
    {
        return route('item.show', ['item_id' => $product->id]);
    }

    #[Test]
    public function ログイン済みユーザーはコメントを送信でき_保存され_コメント数が増える(): void
    {
        $user = User::factory()->create([
            'name' => '廣川 直子',
        ]);

        $product = Product::factory()->create([
            'name'        => 'コメント対象商品',
            'brand'       => 'BrandZ',
            'price'       => 1200,
            'description' => '説明',
        ]);

        $this->actingAs($user);

        $postResponse = $this->post(route('comments.store', ['item_id' => $product->id]), [
            'body' => 'テストコメントです',
        ]);

        $postResponse->assertStatus(302);

        $res  = $this->get($this->productShowUrl($product))->assertOk();
        $html = $res->getContent();

        $this->assertMatchesRegularExpression(
            '/コメント（\s*<span[^>]*id="comments-total"[^>]*>\s*1\s*<\/span>\s*）/u',
            $html
        );

        $res->assertSee('テストコメントです', false);
    }

    #[Test]
    public function ログインしていないユーザーはコメントを送信できない(): void
    {

        $product = Product::factory()->create();

        $res = $this->post(route('comments.store', ['item_id' => $product->id]), [
            'body' => '未ログインでの投稿',
        ]);

        $res->assertRedirect(route('login'));
    }

    #[Test]
    public function コメント未入力ならバリデーションエラーが表示され保存されない(): void
    {

        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user);

        $res = $this->post(route('comments.store', ['item_id' => $product->id]), [
            'body' => '',
        ]);

        $res->assertStatus(302);
        $res->assertSessionHasErrors([
            'body',
        ]);

    }

    #[Test]
    public function コメントが255文字を超えるとバリデーションエラーが表示され保存されない(): void
    {

        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user);

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
