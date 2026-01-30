<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;

class ItemMylistTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function shows_only_favorited_products_in_mylist(): void
    {
        $user = User::factory()->create();
        $favProduct    = Product::factory()->create(['name' => 'お気に入りA']);
        $nonFavProduct = Product::factory()->create(['name' => '未お気に入りB']);

        DB::table('favorites')->insert([
            'user_id'    => $user->id,
            'product_id' => $favProduct->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/?tab=mylist');

        $response->assertOk();
        $response->assertSee('お気に入りA', false);
        $response->assertDontSee('未お気に入りB', false);
    }

    #[Test]
    public function shows_sold_badge_for_sold_products_in_mylist(): void
    {
        $user = User::factory()->create();
        $soldFavorited = Product::factory()->create([
            'name'    => '購入済みアイテム',
            'is_sold' => true,
        ]);

        DB::table('favorites')->insert([
            'user_id'    => $user->id,
            'product_id' => $soldFavorited->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/?tab=mylist');

        $response->assertOk();
        $response->assertSee('Sold', false);
        $response->assertSee('購入済みアイテム', false);
    }

    #[Test]
    public function shows_nothing_for_mylist_when_guest(): void
    {
        $response = $this->get('/?tab=mylist');

        $response->assertOk();
        $response->assertDontSee('product-list__items', false);
        $response->assertDontSee('product-card', false);
        $response->assertDontSee('Sold', false);
    }
}
