<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Favorite;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

class FavoriteToggleTest extends TestCase
{
    use RefreshDatabase;

    private function productShowUrl(Product $product): string
    {
        return Route::has('item.show')
            ? route('item.show', ['item_id' => $product->id])
            : '/item/'.$product->id;
    }

    #[Test]
    public function いいねを押下すると登録され合計値が増える_アイコンは押下状態で表示される(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create(['name' => '家電']);
        $product  = Product::factory()->create([
            'name'        => 'テスト商品',
            'brand'       => 'BrandX',
            'price'       => 1234,
            'description' => '説明',
            'condition'   => '新品',
            'category_id' => $category->id,
        ]);

        $this->assertSame(0, Favorite::where('product_id', $product->id)->count());

        $this->actingAs($user)
             ->get($this->productShowUrl($product))
             ->assertOk();

        $toggleUrl = route('product.favorite.toggle', ['product' => $product->id]);
        $this->followingRedirects()
             ->post($toggleUrl)
             ->assertOk();
        $this->assertDatabaseHas('favorites', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);

        $res = $this->get($this->productShowUrl($product))->assertOk();

        $html = $res->getContent();

        $this->assertMatchesRegularExpression(
            '/id="favorite-count"[^>]*>\s*1\s*<\/span>/u',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/<button[^>]*class="[^"]*\bfavorite-button\b[^"]*\bfavorited\b[^"]*"/u',
            $html
        );
    }

    #[Test]
    public function 再度押下でいいね解除され合計値が減る_アイコンは非押下状態で表示される(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create(['name' => '家電']);
        $product  = Product::factory()->create([
            'name'        => 'テスト商品2',
            'brand'       => 'BrandY',
            'price'       => 3000,
            'description' => '説明2',
            'condition'   => '未使用に近い',
            'category_id' => $category->id,
        ]);

        Favorite::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);

        $this->assertSame(1, Favorite::where('product_id', $product->id)->count());

        $this->actingAs($user)
             ->get($this->productShowUrl($product))
             ->assertOk();

        $toggleUrl = route('product.favorite.toggle', ['product' => $product->id]);
        $this->followingRedirects()
             ->post($toggleUrl)
             ->assertOk();

        $this->assertDatabaseMissing('favorites', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);

        $res = $this->get($this->productShowUrl($product))->assertOk();
        $html = $res->getContent();

        $this->assertMatchesRegularExpression(
            '/id="favorite-count"[^>]*>\s*0\s*<\/span>/u',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/<button[^>]*class="[^"]*\bfavorite-button\b(?![^"]*\bfavorited\b)[^"]*"/u',
            $html
        );
    }
}
