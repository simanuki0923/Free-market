<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class PurchaseAddressTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_access_edit_or_update_and_is_redirected_to_login(): void
    {
        $response = $this->get(route('purchase.address.edit', ['item_id' => 1]));
        $response->assertRedirect(route('login'));

        $response = $this->patch(route('purchase.address.update'), [
            'item_id'     => 1,
            'postal_code' => '123-4567',
            'address1'    => '東京都千代田区1-1-1',
        ]);
        $response->assertRedirect(route('login'));
    }

    // item_id 不在は edit では URL 生成時に落ちるため、update 側で検証する
    #[Test]
    public function update_without_item_id_redirects_to_item_with_error_flash(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // item_id を渡さず PATCH → コントローラのガードで item へリダイレクト想定
        $response = $this->patch(route('purchase.address.update'), [
            // 'item_id' => (missing)
            'postal_code' => '100-0001',
            'address1'    => '東京都千代田区千代田1-1',
        ]);

        // リダイレクト先
        $response->assertRedirect(route('item'));

        // バリデーション/withErrors() 由来のエラーバッグがあること
        $response->assertSessionHasErrors();

        // 画面には描画されていないため、セッションのエラーバッグ内容を直接確認
        $errors = session('errors');
        $this->assertNotNull($errors, 'errors バッグが存在しません');
        $this->assertContains(
            '商品情報が取得できませんでした。',
            $errors->all(),
            '期待するエラーメッセージが errors バッグに存在しません'
        );
    }

    #[Test]
    public function edit_form_displays_with_existing_profile_and_hidden_item_id(): void
    {
        $user    = User::factory()->create();
        $seller  = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price'   => 12345,
            'name'    => 'テスト商品',
            'is_sold' => false,
        ]);

        Profile::factory()->create([
            'user_id'     => $user->id,
            'postal_code' => '100-0001',
            'address1'    => '東京都千代田区千代田1-1',
            'address2'    => '皇居前ハイツ101',
            'phone'       => '0312345678',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('purchase.address.edit', ['item_id' => $product->id]));
        $response->assertStatus(200);
        $response->assertSee('住所の変更');

        // hidden item_id
        $response->assertSee('name="item_id"', false);
        $response->assertSee('value="'.$product->id.'"', false);

        // 既存値の初期表示
        $response->assertSee('value="100-0001"', false);
        $response->assertSee('value="東京都千代田区千代田1-1"', false);
        $response->assertSee('value="皇居前ハイツ101"', false);
    }

    #[Test]
    public function update_validation_errors_when_required_fields_are_missing_or_invalid(): void
    {
        $user    = User::factory()->create();
        $seller  = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price'   => 5000,
            'name'    => '本',
            'is_sold' => false,
        ]);

        $this->actingAs($user);

        // postal_code と address1 の欠落
        $response = $this->from(route('purchase.address.edit', ['item_id' => $product->id]))
            ->patch(route('purchase.address.update'), [
                'item_id'  => $product->id,
                'address2' => 'ビル2F',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['postal_code', 'address1']);

        // postal_code の形式不正（例：ハイフン無し）
        $response = $this->from(route('purchase.address.edit', ['item_id' => $product->id]))
            ->patch(route('purchase.address.update'), [
                'item_id'     => $product->id,
                'postal_code' => '1234567',
                'address1'    => '東京都港区1-1-1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['postal_code']);
    }

    #[Test]
    public function update_successfully_saves_profile_and_redirects_back_to_purchase_with_flash(): void
    {
        $user    = User::factory()->create();
        $seller  = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price'   => 9999,
            'name'    => '更新テスト商品',
            'is_sold' => false,
        ]);

        $this->actingAs($user);

        $payload = [
            'item_id'     => $product->id,
            'postal_code' => '150-0001',
            'address1'    => '東京都渋谷区神宮前1-2-3',
            'address2'    => 'テストビル3F',
            'phone'       => '08011112222',
        ];

        $response = $this->patch(route('purchase.address.update'), $payload);

        $response->assertRedirect(route('purchase', ['item_id' => $product->id]));
        $response->assertSessionHas('success');

        $profile = Profile::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('150-0001', $profile->postal_code);
        $this->assertSame('東京都渋谷区神宮前1-2-3', $profile->address1);
        $this->assertSame('テストビル3F', $profile->address2);
        $this->assertSame('08011112222', $profile->phone);
    }

    #[Test]
    public function purchase_page_displays_updated_address_after_successful_update(): void
    {
        $user    = User::factory()->create();
        $seller  = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price'   => 12000,
            'name'    => '画面反映確認商品',
            'is_sold' => false,
        ]);

        $this->actingAs($user);

        $this->patch(route('purchase.address.update'), [
            'item_id'     => $product->id,
            'postal_code' => '160-0022',
            'address1'    => '東京都新宿区新宿2-2-2',
            'address2'    => '更新後マンション202',
        ])->assertRedirect(route('purchase', ['item_id' => $product->id]));

        $resp = $this->get(route('purchase', ['item_id' => $product->id]));
        $resp->assertStatus(200);
        $resp->assertSee('160-0022');
        $resp->assertSee('東京都新宿区新宿2-2-2');
        $resp->assertSee('更新後マンション202');
    }
}
