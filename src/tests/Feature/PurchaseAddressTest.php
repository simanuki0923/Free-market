<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseAddressTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_access_edit_or_update_and_is_redirected_to_login(): void
    {
        $res1 = $this->get(route('purchase.address.edit', ['item_id' => 1]));
        $res1->assertRedirect(route('login'));
        $res2 = $this->post(route('purchase.address.update'), [
            '_method'      => 'PATCH',
            'item_id'      => 1,
            'postal_code'  => '123-4567',
            'address1'     => '東京都千代田区1-2-3',
            'address2'     => 'テストビル4F',
            'phone'        => '0312345678',
        ]);
        $res2->assertRedirect(route('login'));
    }

    #[Test]
    public function update_without_item_id_redirects_to_item_with_error_flash(): void
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();

        $this->actingAs($user);

        $response = $this->post(route('purchase.address.update'), [
            '_method'      => 'PATCH',
            'postal_code'  => '123-4567',
            'address1'     => '東京都千代田区1-2-3',
            'address2'     => 'テストビル4F',
            'phone'        => '0312345678',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('item'));

        $response->assertSessionHasErrors();
    }

    #[Test]
    public function edit_form_displays_with_existing_profile_and_hidden_item_id(): void
    {
        $user = User::factory()->create();

        Profile::factory()->for($user)->create([
            'postal_code' => '100-0001',
            'address1'    => '東京都千代田区千代田1-1',
            'address2'    => '皇居前ハイツ101',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('purchase.address.edit', ['item_id' => 1]))
            ->assertOk();

        $response->assertSee('value="100-0001"', false);
        $response->assertSee('value="東京都千代田区千代田1-1"', false);
        $response->assertSee('value="皇居前ハイツ101"', false);
        $response->assertSee('name="item_id"', false)
                 ->assertSee('value="1"', false);
    }

    #[Test]
    public function update_validation_errors_when_required_fields_are_missing_or_invalid(): void
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();

        $this->actingAs($user);

        $response = $this->from(route('purchase.address.edit', ['item_id' => 99]))->post(
            route('purchase.address.update'),
            [
                '_method'      => 'PATCH',
                'item_id'      => 99,
                'postal_code'  => '',
                'address1'     => '',
                'address2'     => '',
                'phone'        => '0300000000',
            ]
        );

        $response->assertStatus(302)
                 ->assertRedirect(route('purchase.address.edit', ['item_id' => 99]));

        $response->assertSessionHasErrors();
    }

    #[Test]
    public function update_successfully_saves_profile_and_redirects_back_to_purchase_with_flash(): void
    {
        $user = User::factory()->create();

        Profile::factory()->for($user)->create();

        $product = Product::factory()->create([
            'id' => 1,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('purchase.address.update'), [
            '_method'      => 'PATCH',
            'item_id'      => $product->id,
            'postal_code'  => '150-0001',
            'address1'     => '東京都渋谷区神宮前1-2-3',
            'address2'     => 'テストビル3F',
            'phone'        => '08011112222',
        ]);

        $response->assertStatus(302)
                 ->assertRedirect(route('purchase', ['item_id' => $product->id]));

        $profile = Profile::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('150-0001', $profile->postal_code);
        $this->assertSame('東京都渋谷区神宮前1-2-3', $profile->address1);
        $this->assertSame('テストビル3F', $profile->address2);

    }

    #[Test]
    public function purchase_page_displays_updated_address_after_successful_update(): void
    {
        $user = User::factory()->create();

        Profile::factory()->for($user)->create([
            'postal_code' => '111-1111',
            'address1'    => '東京都世田谷区1-1-1',
            'address2'    => '旧マンション201',
        ]);

        $product = Product::factory()->create([
            'id' => 5,
        ]);

        $this->actingAs($user);

        $updateResponse = $this->post(route('purchase.address.update'), [
            '_method'      => 'PATCH',
            'item_id'      => $product->id, // 5
            'postal_code'  => '222-2222',
            'address1'     => '東京都港区2-2-2',
            'address2'     => '新タワー99F',
            'phone'        => '08099998888',
        ]);

        $updateResponse->assertStatus(302)
                       ->assertRedirect(route('purchase', ['item_id' => $product->id]));

        $page = $this->get(route('purchase', ['item_id' => $product->id]))
                     ->assertOk();

        $page->assertSee('222-2222', false);
        $page->assertSee('東京都港区2-2-2', false);
        $page->assertSee('新タワー99F', false);

    }
}
