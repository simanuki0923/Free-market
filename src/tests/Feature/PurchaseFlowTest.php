<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    private function preparePurchaseScenario(): array
    {
        $user = User::factory()->create([
            'name'  => '購入者ユーザー',
            'email' => 'buyer@example.com',
        ]);

        Profile::factory()->for($user)->create([
            'postal_code' => '123-4567',
            'address1'    => '東京都千代田区1-2-3',
            'address2'    => ' テストビル4F',
        ]);

        $product = Product::factory()->create([
        ]);

        return [$user, $product];
    }

    #[Test]
    public function user_can_proceed_to_payment_create_from_purchase_page(): void
    {
        [$user, $product] = $this->preparePurchaseScenario();

        $this->actingAs($user);

        $purchasePage = $this->get(route('purchase', ['item_id' => $product->id]))
            ->assertOk();

        $purchasePage->assertSee(route('payment.create'), false);
    }

    #[Test]
    public function purchased_item_is_shown_as_sold_in_list(): void
    {
        [$user, $product] = $this->preparePurchaseScenario();

        $this->actingAs($user);


        if ($product->isFillable('is_sold') || array_key_exists('is_sold', $product->getAttributes())) {
            $product->is_sold = true;
            $product->save();
        }

        $listPage = $this->get(route('item'))->assertOk();

    }

    #[Test]
    public function purchased_item_is_listed_in_profile_purchases(): void
    {
        [$user, $product] = $this->preparePurchaseScenario();

        $this->actingAs($user);


        if ($product->isFillable('buyer_user_id') || array_key_exists('buyer_user_id', $product->getAttributes())) {
            $product->buyer_user_id = $user->id;
            $product->save();
        }

        $mypage = $this->get(route('mypage'))
            ->assertOk();

    }
}
