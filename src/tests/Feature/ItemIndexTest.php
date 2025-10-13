<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ItemIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_see_all_products_on_all_tab(): void
    {
        $p1 = Product::factory()->create(['name' => 'AAA-Product']);
        $p2 = Product::factory()->create(['name' => 'BBB-Product']);

        $res = $this->get(route('item', ['tab' => 'all']));

        $res->assertOk()
            ->assertSee('AAA-Product', false)
            ->assertSee('BBB-Product', false);
    }

    public function test_sold_label_is_shown_for_sold_products(): void
    {
        $sold = Product::factory()->create(['name' => 'Sold-Target', 'is_sold' => true]);
        $other = Product::factory()->create(['name' => 'Normal-Product', 'is_sold' => false]);

        $res = $this->get(route('item', ['tab' => 'all']));

        $res->assertOk()
            ->assertSee('Sold-Target', false)
            ->assertSee('Sold', false)
            ->assertSee('Normal-Product', false);
    }

    public function test_own_products_are_hidden_for_logged_in_user_on_all_tab(): void
    {
        $me = User::factory()->create();
        $myProduct = Product::factory()->create(['name' => 'My-Secret-Listing', 'user_id' => $me->id]);
        $others = Product::factory()->create(['name' => 'Others-Listing']);

        $res = $this->actingAs($me)->get(route('item', ['tab' => 'all']));

        $res->assertOk()
            ->assertDontSee('My-Secret-Listing', false)
            ->assertSee('Others-Listing', false);
    }
}
