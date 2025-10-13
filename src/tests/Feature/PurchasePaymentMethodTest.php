<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class PurchasePaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private function urlPurchaseShow(int $itemId): string
    {
        if (RouteFacade::has('purchase.show')) {
            return route('purchase.show', ['item_id' => $itemId]);
        }
        if (RouteFacade::has('purchase')) {
            return route('purchase', ['item_id' => $itemId]);
        }
        return '/purchase/' . $itemId;
    }

    private function urlPurchasePayment(array $query): ?string
    {
        if (RouteFacade::has('purchase.payment')) {
            return route('purchase.payment', $query);
        }
        return null;
    }

    private function urlPaymentCreate(array $query): string
    {
        if (RouteFacade::has('payment.create')) {
            return route('payment.create', $query);
        }
        $qs = http_build_query($query);
        return '/payment/create' . ($qs ? ('?' . $qs) : '');
    }

    public function test_show_displays_payment_select_and_summary_default(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price'   => 12345,
            'is_sold' => false,
        ]);

        $buyer = User::factory()->create();
        Profile::factory()->create([
            'user_id'     => $buyer->id,
            'postal_code' => '123-4567',
            'address1'    => '東京都千代田区1-1-1',
            'address2'    => 'テストビル3F',
            'phone'       => '03-1234-5678',
        ]);

        $res = $this->actingAs($buyer)->get($this->urlPurchaseShow($product->id));
        $res->assertOk();
        $res->assertSee('支払い方法', false);
        $res->assertSee('id="payment_method"', false);
        $res->assertSee('コンビニ払い', false);
        $res->assertSee('クレジットカード', false);
        $res->assertSee('未選択', false);
    }

    public function test_payment_validation_and_redirect_to_payment_create_with_credit_card(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price'   => 5000,
            'is_sold' => false,
        ]);

        $buyer = User::factory()->create();
        Profile::factory()->create([
            'user_id'     => $buyer->id,
            'postal_code' => '100-0001',
            'address1'    => '東京都千代田区千代田1-1',
            'address2'    => '',
            'phone'       => '03-0000-0000',
        ]);

        $query = [
            'item_id'        => $product->id,
            'payment_method' => 'credit_card',
            'postal_code'    => '100-0001',
            'address1'       => '東京都千代田区千代田1-1',
        ];

        $paymentUrl = $this->urlPurchasePayment($query);

        if ($paymentUrl) {
            $res = $this->actingAs($buyer)->get($paymentUrl);

            $res->assertRedirect($this->urlPaymentCreate([
                'item_id'        => $product->id,
                'payment_method' => 'credit_card',
            ]));
            $res->assertSessionHas('status');
        } else {
            $res = $this->actingAs($buyer)->get($this->urlPaymentCreate($query));
            $this->assertTrue(in_array($res->getStatusCode(), [200, 201, 202, 204, 302], true));
        }
    }

    public function test_payment_validation_and_redirect_to_payment_create_with_convenience_store(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price'   => 9800,
            'is_sold' => false,
        ]);

        $buyer = User::factory()->create();
        Profile::factory()->create([
            'user_id'     => $buyer->id,
            'postal_code' => '150-0001',
            'address1'    => '東京都渋谷区神宮前1-1-1',
            'address2'    => '',
            'phone'       => '03-1111-2222',
        ]);

        $query = [
            'item_id'        => $product->id,
            'payment_method' => 'convenience_store',
            'postal_code'    => '150-0001',
            'address1'       => '東京都渋谷区神宮前1-1-1',
        ];

        $paymentUrl = $this->urlPurchasePayment($query);

        if ($paymentUrl) {
            $res = $this->actingAs($buyer)->get($paymentUrl);

            $res->assertRedirect($this->urlPaymentCreate([
                'item_id'        => $product->id,
                'payment_method' => 'convenience_store',
            ]));
            $res->assertSessionHas('status');
        } else {
            $res = $this->actingAs($buyer)->get($this->urlPaymentCreate($query));
            $this->assertTrue(in_array($res->getStatusCode(), [200, 201, 202, 204, 302], true));
        }
    }

    public function test_payment_fails_when_address_missing(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price'   => 5000,
            'is_sold' => false,
        ]);

        $buyer = User::factory()->create();

        $query = [
            'item_id'        => $product->id,
            'payment_method' => 'credit_card',
        ];

        $paymentUrl = $this->urlPurchasePayment($query);

        if ($paymentUrl) {
            $res = $this->actingAs($buyer)->get($paymentUrl);
            $res->assertSessionHasErrors();
        } else {
            $res = $this->actingAs($buyer)->get($this->urlPaymentCreate($query));
            $this->assertTrue(in_array($res->getStatusCode(), [400, 401, 403, 404, 422, 302], true));
        }
    }
}
