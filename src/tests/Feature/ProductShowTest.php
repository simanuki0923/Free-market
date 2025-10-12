<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Profile;
use App\Models\Favorite;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

class ProductShowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 商品詳細ページで必要な情報がすべて表示される(): void
    {
        // === 準備：出品者・カテゴリ・商品 ===
        $seller   = User::factory()->create(['name' => '出品者A']);
        $category = Category::factory()->create(['name' => '家電']); // slug は Factory 側で自動生成
        $product  = Product::factory()
            ->for($seller, 'user')
            ->create([
                'name'        => '高性能ドライヤー',
                'brand'       => 'SuperWind',
                'price'       => 19800,
                'description' => '風量が強く速乾タイプです。',
                'condition'   => '未使用に近い',
                'is_sold'     => false,
                'category_id' => $category->id,   // 単一カテゴリ（belongsTo）
            ]);

        // === 準備：お気に入り 2 件 ===
        $u1 = User::factory()->create(['name' => '太郎']);
        $u2 = User::factory()->create(['name' => '花子']);
        Favorite::factory()->create(['user_id' => $u1->id, 'product_id' => $product->id]);
        Favorite::factory()->create(['user_id' => $u2->id, 'product_id' => $product->id]);

        // === 準備：コメント 2 件（プロフィールはアイコン null 許容）===
        $cmtUser1 = User::factory()->create(['name' => 'コメントユーザー1']);
        $cmtUser2 = User::factory()->create(['name' => 'コメントユーザー2']);
        Profile::factory()->create(['user_id' => $cmtUser1->id, 'icon_image_path' => null]);
        Profile::factory()->create(['user_id' => $cmtUser2->id, 'icon_image_path' => null]);
        Comment::factory()->for($product)->for($cmtUser1, 'user')->create(['body' => '良さそうですね']);
        Comment::factory()->for($product)->for($cmtUser2, 'user')->create(['body' => '購入を検討中です']);

        // === 実行：現在のルート定義（/item/{item_id}, name:item.show）に合わせてアクセス ===
        $url = Route::has('item.show')
            ? route('item.show', ['item_id' => $product->id])
            : '/item/'.$product->id;

        $response = $this->get($url);
        $response->assertOk();

        // === 検証：基本情報 ===
        $response->assertSee('高性能ドライヤー', false);
        $response->assertSee('SuperWind', false);
        $response->assertSee('19,800', false);   // number_format
        $response->assertSee('（税込）', false); // 表示がある仕様なら

        // === 検証：説明・商品情報（単一カテゴリ表示）===
        $response->assertSee('風量が強く速乾タイプです。', false);
        $response->assertSee('カテゴリー：', false);
        $response->assertSee('家電', false);
        $response->assertSee('商品の状態：未使用に近い', false);

        // === 検証：件数（タグ込みの表記に対応）===
        $html = $response->getContent();
        // コメント（<span id="comments-total">2</span>件） のような構造にマッチ
        $this->assertMatchesRegularExpression(
            '/コメント（\s*<span[^>]*id="comments-total"[^>]*>\s*2\s*<\/span>\s*件）/u',
            $html
        );

        // お気に入り数の数字（2）自体が画面に出ていること（バッジ等でもOK）
        $response->assertSee('2', false);

        // === 検証：コメント内容が実際に表示されていること ===
        $response->assertSee('コメントユーザー1', false);
        $response->assertSee('コメントユーザー2', false);
        $response->assertSee('良さそうですね', false);
        $response->assertSee('購入を検討中です', false);
    }
}
