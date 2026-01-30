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
        Product::factory()->create(['name' => 'AirPods Pro 2']);
        Product::factory()->create(['name' => 'Galaxy Buds 3']);
        $res = $this->get(route('search', ['search' => 'Air']));
        $res->assertOk();
        $res->assertSee('AirPods Pro 2');
        $res->assertDontSee('Galaxy Buds 3');
    }

    #[Test]
    public function 検索キーワードがマイリスト遷移後も保持される()
    {
        $user = User::factory()->create();
        $keyword = 'Camera';
        $res = $this->get(route('search', ['search' => $keyword]));
        $res->assertOk();
        $html = $res->getContent();
        $this->assertStringContainsString('tab=mylist', $html, 'マイリストタブへのリンクがありません');
        $this->assertStringContainsString('search=' . $keyword, $html, '検索クエリが引き継がれていません');
        $this->actingAs($user);
        $mylist = $this->get(route('item', ['tab' => 'mylist', 'search' => $keyword]));
        $mylist->assertOk();
        $mylist->assertSee('value="'.$keyword.'"', false);
    }
}
