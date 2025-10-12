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
        // ルートは現状の定義（/item/{item_id}, name:item.show）に合わせる
        return Route::has('item.show')
            ? route('item.show', ['item_id' => $product->id])
            : '/item/'.$product->id;
    }

    #[Test]
    public function いいねを押下すると登録され合計値が増える_アイコンは押下状態で表示される(): void
    {
        // --- 準備（ユーザー・カテゴリ・商品）---
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

        // --- 期待：初期状態ではお気に入り0 ---
        $this->assertSame(0, Favorite::where('product_id', $product->id)->count());

        // --- 手順：ログイン → 商品詳細を開く（初期表示確認は任意）---
        $this->actingAs($user)
             ->get($this->productShowUrl($product))
             ->assertOk();

        // --- 手順：いいねトグルを押下（POST /product/{product}/favorite）---
        $toggleUrl = route('product.favorite.toggle', ['product' => $product->id]);
        $this->followingRedirects()
             ->post($toggleUrl)
             ->assertOk(); // FavoriteController は back() を返す想定

        // --- 期待：DBに登録されている ---
        $this->assertDatabaseHas('favorites', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);

        // --- 期待：再表示時、合計値が1へ増加、ボタンは押下状態（.favorited クラス） ---
        $res = $this->get($this->productShowUrl($product))->assertOk();

        $html = $res->getContent();

        // #favorite-count に 1 が表示されている
        $this->assertMatchesRegularExpression(
            '/id="favorite-count"[^>]*>\s*1\s*<\/span>/u',
            $html
        );

        // 押下状態（.favorited クラス付与）
        // product.blade.php の <button class="favorite-button {{ $isFavorited ? 'favorited' : '' }}">
        $this->assertMatchesRegularExpression(
            '/<button[^>]*class="[^"]*\bfavorite-button\b[^"]*\bfavorited\b[^"]*"/u',
            $html
        );
    }

    #[Test]
    public function 再度押下でいいね解除され合計値が減る_アイコンは非押下状態で表示される(): void
    {
        // --- 準備（ユーザー・商品・既に「いいね」済みの状態）---
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

        // 事前に 1 件「いいね」状態を作る
        Favorite::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);

        $this->assertSame(1, Favorite::where('product_id', $product->id)->count());

        $this->actingAs($user)
             ->get($this->productShowUrl($product))
             ->assertOk();

        // --- 手順：同じトグルを再度押下（= 解除動作）---
        $toggleUrl = route('product.favorite.toggle', ['product' => $product->id]);
        $this->followingRedirects()
             ->post($toggleUrl)
             ->assertOk();

        // --- 期待：DB から削除されている ---
        $this->assertDatabaseMissing('favorites', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
        ]);

        // --- 期待：再表示時、合計値が0へ減少、ボタンは非押下（favorited無し） ---
        $res = $this->get($this->productShowUrl($product))->assertOk();
        $html = $res->getContent();

        $this->assertMatchesRegularExpression(
            '/id="favorite-count"[^>]*>\s*0\s*<\/span>/u',
            $html
        );

        // 非押下：.favorite-button はあるが .favorited は付かない
        $this->assertMatchesRegularExpression(
            '/<button[^>]*class="[^"]*\bfavorite-button\b(?![^"]*\bfavorited\b)[^"]*"/u',
            $html
        );
    }
}
