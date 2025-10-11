<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ItemIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1.「テスト内容」＝ 全商品を取得できる
     *    「テスト手順」＝ 商品ページを開く
     *    「テスト期待挙動」＝ すべての商品が表示される
     *
     * ※ 仕様に忠実に、ゲストで /?tab=all を開いた場合に
     *    登録済みの全商品名が一覧に現れることを確認する。
     */
    public function test_guest_can_see_all_products_on_all_tab(): void
    {
        // Arrange
        $p1 = Product::factory()->create(['name' => 'AAA-Product']);
        $p2 = Product::factory()->create(['name' => 'BBB-Product']);

        // Act
        $res = $this->get(route('item', ['tab' => 'all']));

        // Assert
        $res->assertOk()
            ->assertSee('AAA-Product', false)
            ->assertSee('BBB-Product', false);
    }

    /**
     * 2.「テスト内容」＝ 購入済み商品は「Sold」と表示される
     *    「テスト手順」＝ 商品ページを開く→ 購入済み商品を表示する
     *    「テスト期待挙動」＝ 購入済み商品に「Sold」のラベルが表示される
     *
     * ※ Blade では $product->is_sold が true の場合に
     *    <span class="sold-out-label">Sold</span> を表示する。
     */
    public function test_sold_label_is_shown_for_sold_products(): void
    {
        // Arrange
        $sold = Product::factory()->create(['name' => 'Sold-Target', 'is_sold' => true]);
        $other = Product::factory()->create(['name' => 'Normal-Product', 'is_sold' => false]);

        // Act
        $res = $this->get(route('item', ['tab' => 'all']));

        // Assert
        $res->assertOk()
            ->assertSee('Sold-Target', false)
            ->assertSee('Sold', false)   // ラベル文字の出力確認
            ->assertSee('Normal-Product', false);
    }

    /**
     * 3.「テスト内容」＝ 自分が出品した商品は表示されない
     *    「テスト手順」＝ ユーザーにログインをする→ 商品ページを開く
     *    「テスト期待挙動」＝ 自分が出品した商品が一覧に表示されない
     *
     * ※ ItemController では、tab=all のとき Auth::id() の商品を除外する。
     */
    public function test_own_products_are_hidden_for_logged_in_user_on_all_tab(): void
    {
        // Arrange
        $me = User::factory()->create();
        $myProduct = Product::factory()->create(['name' => 'My-Secret-Listing', 'user_id' => $me->id]);
        $others = Product::factory()->create(['name' => 'Others-Listing']); // 別ユーザの商品想定

        // Act
        $res = $this->actingAs($me)->get(route('item', ['tab' => 'all']));

        // Assert
        $res->assertOk()
            ->assertDontSee('My-Secret-Listing', false)
            ->assertSee('Others-Listing', false);
    }
}
