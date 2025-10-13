<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Sell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SellStoreTest extends TestCase
{
    use RefreshDatabase;

    private function makePngUpload(string $name = 'photo.png', int $minBytes = 0): UploadedFile
    {
        $png1x1 = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII='
        );

        $path = tempnam(sys_get_temp_dir(), 't_png_');
        file_put_contents($path, $png1x1);
        $current = filesize($path);
        if ($minBytes > $current) {
            $pad = str_repeat("\0", $minBytes - $current);
            file_put_contents($path, $pad, FILE_APPEND);
        }

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    #[Test]
    public function guest_cannot_access_sell_store_and_is_redirected_to_login(): void
    {
        $response = $this->post(route('sell.store'), []);
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function it_saves_all_required_listing_fields_and_creates_product_and_sell_with_image(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Storage::fake('public');
        $categoryId = null;
        if (Schema::hasTable('categories')) {
            $data = [
                'name'       => '家電',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('categories', 'slug')) {
                $data['slug'] = 'kaden-' . Str::random(8);
            }
            $categoryId = DB::table('categories')->insertGetId($data);
        }

        $image = $this->makePngUpload('photo.png');

        $payload = [
            'category_id' => $categoryId,
            'condition'   => '未使用に近い',
            'name'        => 'テスト炊飯器',
            'brand'       => 'TESTCOOK',
            'description' => '5合炊き。動作良好。',
            'price'       => 12345,
            'image'       => $image,
        ];

        $response = $this->post(route('sell.store'), $payload);
        $response->assertRedirect(route('item'));
        $response->assertSessionHas('success');

        $product = Product::query()->latest('id')->first();
        $this->assertNotNull($product);

        if (!empty($product->image_path)) {
            Storage::disk('public')->assertExists($product->image_path);
        }

        $this->assertSame($user->id, $product->user_id);
        $this->assertSame($categoryId, $product->category_id);
        $this->assertSame('テスト炊飯器', $product->name);
        $this->assertSame('TESTCOOK', $product->brand);
        $this->assertSame(12345, (int)$product->price);
        $this->assertSame('未使用に近い', $product->condition);
        $this->assertSame('5合炊き。動作良好。', $product->description);
        $this->assertFalse((bool)$product->is_sold);

        $sell = Sell::query()->latest('id')->first();
        $this->assertNotNull($sell);
        $this->assertSame($user->id, $sell->user_id);
        $this->assertSame($product->id, $sell->product_id);
        $this->assertSame($product->category_id, $sell->category_id);
        $this->assertSame($product->name, $sell->name);
        $this->assertSame($product->brand, $sell->brand);
        $this->assertSame($product->price, $sell->price);
        $this->assertSame($product->condition, $sell->condition);
        $this->assertSame($product->description, $sell->description);
        $this->assertFalse((bool)$sell->is_sold);
    }

    #[Test]
    public function it_fails_validation_when_required_fields_are_missing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->from(route('sell.create'))
            ->post(route('sell.store'), [
                'condition'   => '未使用に近い',
                'brand'       => 'TESTCOOK',
                'description' => '説明',
            ]);

        $response->assertRedirect(route('sell.create'));
        $response->assertSessionHasErrors(['name', 'price']);
    }

    #[Test]
    public function price_must_be_integer_and_at_least_zero(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->from(route('sell.create'))
            ->post(route('sell.store'), [
                'name'  => 'NG価格商品',
                'price' => -1,
            ]);

        $response->assertRedirect(route('sell.create'));
        $response->assertSessionHasErrors(['price']);
    }

    #[Test]
    public function it_accepts_without_image_and_correctly_persists_text_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('sell.store'), [
            'name'        => '画像なし商品',
            'brand'       => 'NOBRAND',
            'price'       => 8000,
            'condition'   => '目立った傷や汚れなし',
            'description' => '画像無しでも保存される想定',
        ]);

        $response->assertRedirect(route('item'));
        $response->assertSessionHas('success');

        $product = Product::query()->latest('id')->first();
        $this->assertSame('画像なし商品', $product->name);
        $this->assertSame('NOBRAND', $product->brand);
        $this->assertSame(8000, (int)$product->price);
        $this->assertSame('目立った傷や汚れなし', $product->condition);
        $this->assertSame('画像無しでも保存される想定', $product->description);
        $this->assertNull($product->image_path);
    }

    #[Test]
    public function image_is_validated_as_image_and_max_5mb(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Storage::fake('public');
        $notImagePath = tempnam(sys_get_temp_dir(), 'txt_');
        file_put_contents($notImagePath, 'not image');
        $notImage = new UploadedFile($notImagePath, 'not-image.txt', 'text/plain', null, true);

        $response = $this->from(route('sell.create'))
            ->post(route('sell.store'), [
                'name'  => '不正画像',
                'price' => 1000,
                'image' => $notImage,
            ]);
        $response->assertRedirect(route('sell.create'));
        $response->assertSessionHasErrors(['image']);
        $bigImage = $this->makePngUpload('big.png', 6 * 1024 * 1024);

        $response = $this->from(route('sell.create'))
            ->post(route('sell.store'), [
                'name'  => '大きすぎる画像',
                'price' => 1000,
                'image' => $bigImage,
            ]);
        $response->assertRedirect(route('sell.create'));
        $response->assertSessionHasErrors(['image']);
    }
}
