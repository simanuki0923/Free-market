<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchasePaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private function scenarioWithProfile(): array
    {
        $user = User::factory()->create([
            'name'  => '決済テストユーザー',
            'email' => 'buyer@example.com',
        ]);

        Profile::factory()->for($user)->create([
            'postal_code' => '123-4567',
            'address1'    => '東京都千代田区1-1-1',
            'address2'    => ' テストビル3F',
        ]);

        $product = Product::factory()->create([
        ]);

        return [$user, $product];
    }

    private function scenarioWithoutProfile(): array
    {
        $user = User::factory()->create([
            'name'  => '住所未登録ユーザー',
            'email' => 'noaddress@example.com',
        ]);

        $product = Product::factory()->create();

        return [$user, $product];
    }

    #[Test]
    public function show_displays_payment_select_and_summary_default(): void
    {
        [$user, $product] = $this->scenarioWithProfile();

        $this->actingAs($user);
        $this->get(route('purchase', ['item_id' => $product->id]))
             ->assertOk();

    }

    #[Test]
    public function payment_validation_and_redirect_to_payment_create_with_credit_card(): void
    {
        [$user, $product] = $this->scenarioWithProfile();
        $this->actingAs($user);

        $response = $this->post(route('payment.store'), [
            'item_id'         => $product->id,
            'payment_method'  => 'credit_card',
            'card_number'     => '4111111111111111',
            'card_exp'        => '12/30',
            'card_cvc'        => '123',
        ]);

        $response->assertStatus(302);
    }

    #[Test]
    public function payment_validation_and_redirect_to_payment_create_with_convenience_store(): void
    {
        [$user, $product] = $this->scenarioWithProfile();
        $this->actingAs($user);

        $response = $this->post(route('payment.store'), [
            'item_id'         => $product->id,
            'payment_method'  => 'convenience_store',
        ]);

        $response->assertStatus(302);
    }

    #[Test]
    public function payment_fails_when_address_missing(): void
    {
        [$user, $product] = $this->scenarioWithoutProfile();
        $this->actingAs($user);

        $response = $this->post(route('payment.store'), [
            'item_id'         => $product->id,
            'payment_method'  => 'credit_card',
            'card_number'     => '4111111111111111',
            'card_exp'        => '12/30',
            'card_cvc'        => '123',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors();
    }
}
