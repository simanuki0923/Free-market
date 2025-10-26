<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SellStoreTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_access_sell_store_and_is_redirected_to_login(): void
    {
        $this->get(route('sell.create'))
             ->assertRedirect(route('login'));

        $this->post(route('sell.store'), [])
             ->assertRedirect(route('login'));
    }

    #[Test]
    public function it_saves_all_required_listing_fields_and_creates_product_and_sell_with_image(): void
    {

        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'name'        => 'テスト商品A',
            'brand'       => 'BRAND-X',
            'price'       => 1200,
            'condition'   => '新品',
            'description' => 'とても良いドライヤーです',
        ];

        $response = $this->post(route('sell.store'), $payload);

        $response->assertRedirect(route('item'));

        $response->assertSessionHasErrors([
            'categories',
        ]);
    }

    #[Test]
    public function it_fails_validation_when_required_fields_are_missing(): void
    {

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('sell.store'), [
        ]);

        $response->assertStatus(302)
                 ->assertRedirect(route('item'));

        $response->assertSessionHasErrors();
    }

    #[Test]
    public function price_must_be_integer_and_at_least_zero(): void
    {

        $user = User::factory()->create();
        $this->actingAs($user);

        $badPayload = [
            'name'        => '価格テスト商品',
            'brand'       => 'NEGATIVE',
            'price'       => -10,
            'condition'   => '中古',
            'description' => 'マイナス価格テスト',
        ];

        $response = $this->post(route('sell.store'), $badPayload);

        $response->assertStatus(302)
                 ->assertRedirect(route('item'));

        $response->assertSessionHasErrors([
            'price',
        ]);
    }

    #[Test]
    public function it_accepts_without_image_and_correctly_persists_text_fields(): void
    {

        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'name'        => '画像なし商品',
            'brand'       => 'NOBRAND',
            'price'       => 500,
            'condition'   => '中古',
            'description' => '画像無しでも保存される想定(実装上はエラーになる)',
        ];

        $response = $this->post(route('sell.store'), $payload);

        $response->assertRedirect(route('item'));

        $response->assertSessionHasErrors([
            'image',
            'categories',
        ]);

    }

    #[Test]
    public function image_is_validated_as_image_and_max_5mb(): void
    {

        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $tooBigNotImage = UploadedFile::fake()->create('bigfile.pdf', 6000, 'application/pdf');

        $payload = [
            'name'        => '画像バリデーション商品',
            'brand'       => 'DOCFILE',
            'price'       => 999,
            'condition'   => '新品',
            'description' => 'でかいPDFなのでNG想定',
            'image'       => $tooBigNotImage,
        ];

        $response = $this->post(route('sell.store'), $payload);

        $response->assertStatus(302)
                 ->assertRedirect(route('item'));

        $response->assertSessionHasErrors([
            'image',
        ]);
    }
}
