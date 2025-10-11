<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SearchFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 商品名の部分一致検索で該当商品が表示される()
    {
        // Arrange
        Product::factory()->create(['name' => 'AirPods Pro 2']);
        Product::factory()->create(['name' => 'Galaxy Buds 3']);

        // Act: 検索は search パラメータで統一
        $res = $this->get(route('search', ['search' => 'Air']));

        // Assert
        $res->assertOk();
        $res->assertSee('AirPods Pro 2');
        $res->assertDontSee('Galaxy Buds 3');
    }

    #[Test]
    public function 検索キーワードがマイリスト遷移後も保持される()
    {
        // Arrange
        $user = User::factory()->create();
        $keyword = 'Camera';

        // 検索結果ページを開く
        $res = $this->get(route('search', ['search' => $keyword]));
        $res->assertOk();

        // 検索結果画面に「マイリスト」タブがあり、検索クエリがHTML内に引き継がれていること
        $html = $res->getContent();
        $this->assertStringContainsString('tab=mylist', $html, 'マイリストタブへのリンクがありません');
        $this->assertStringContainsString('search=' . $keyword, $html, '検索クエリが引き継がれていません');

        // 実際にマイリストタブへ遷移（クエリ付き）
        $this->actingAs($user);
        $mylist = $this->get(route('item', ['tab' => 'mylist', 'search' => $keyword]));
        $mylist->assertOk();

        // ヘッダー or ページ内の検索フォームの value にキーワードが保持されていること
        $mylist->assertSee('value="'.$keyword.'"', false);
    }
}
