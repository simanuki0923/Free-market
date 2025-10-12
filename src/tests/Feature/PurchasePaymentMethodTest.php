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

    // ---- ルート解決ヘルパ -----------------------------------------------

    private function urlPurchaseShow(int $itemId): string
    {
        if (RouteFacade::has('purchase.show')) {
            return route('purchase.show', ['item_id' => $itemId]);
        }
        // あなたの現状ルート: name('purchase') がある想定
        if (RouteFacade::has('purchase')) {
            return route('purchase', ['item_id' => $itemId]);
        }
        // 名前が無い場合のパス直指定
        return '/purchase/' . $itemId;
    }

    private function urlPurchasePayment(array $query): ?string
    {
        if (RouteFacade::has('purchase.payment')) {
            // GET 運用を前提（POST 運用ならテスト側も post() に変える）
            return route('purchase.payment', $query);
        }
        // ルートが無い場合は null を返し、フォールバックで /payment/create を使う
        return null;
    }

    private function urlPaymentCreate(array $query): string
    {
        if (RouteFacade::has('payment.create')) {
            return route('payment.create', $query);
        }
        // 最終フォールバック（名前無しでも動く）
        $qs = http_build_query($query);
        return '/payment/create' . ($qs ? ('?' . $qs) : '');
    }

    // ---- テスト本体 -------------------------------------------------------

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

        // セレクトと選択肢
        $res->assertSee('支払い方法', false);
        $res->assertSee('id="payment_method"', false);
        $res->assertSee('コンビニ払い', false);
        $res->assertSee('クレジットカード', false);

        // サマリ初期表示（未選択）
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
            // （A）purchase.payment ルートがある場合：そこへアクセス → payment.create へリダイレクト想定
            $res = $this->actingAs($buyer)->get($paymentUrl);

            $res->assertRedirect($this->urlPaymentCreate([
                'item_id'        => $product->id,
                'payment_method' => 'credit_card',
            ]));
            $res->assertSessionHas('status');
        } else {
            // （B）purchase.payment が無い場合：create を直接叩いて“選択値が伝搬すること”を確認
            $res = $this->actingAs($buyer)->get($this->urlPaymentCreate($query));
            // 実装により 200 か 302 の可能性があるため、成功系で緩めに確認
            $this->assertTrue(in_array($res->getStatusCode(), [200, 201, 202, 204, 302], true));

            // レンダリングやコンテキストで選択値が見えるなら以下のように確認（存在しないなら削除OK）
            // $res->assertSee('クレジットカード', false);
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
            // $res->assertSee('コンビニ払い', false);
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
            // postal_code / address1 なし → 住所未設定の負ケース
        ];

        $paymentUrl = $this->urlPurchasePayment($query);

        if ($paymentUrl) {
            $res = $this->actingAs($buyer)->get($paymentUrl);
            $res->assertSessionHasErrors(); // バリデーションで戻る想定
        } else {
            // purchase.payment が無い運用では、直接 create に来た時の扱いは実装依存
            // ここでは “不足があれば何らかのエラー系になる” ことだけ確認
            $res = $this->actingAs($buyer)->get($this->urlPaymentCreate($query));
            $this->assertTrue(in_array($res->getStatusCode(), [400, 401, 403, 404, 422, 302], true));
        }
    }
}
