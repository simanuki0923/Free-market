<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'category_id' => null, // 単一カテゴリ(belongsTo)は必要時に付与
            'name'        => $this->faker->unique()->words(3, true),
            'brand'       => $this->faker->optional()->randomElement([
                'Apple','Sony','Canon','Panasonic','Nintendo','ASUS','Other'
            ]),
            'price'       => $this->faker->numberBetween(1000, 50000),
            'image_path'  => null,
            'condition'   => $this->faker->optional()->randomElement([
                '新品', '未使用に近い', '目立った傷や汚れなし', 'やや傷や汚れあり', '全体的に状態が悪い'
            ]),
            'description' => $this->faker->optional()->sentence(12),
            'is_sold'     => false,
        ];
    }

    /* ---------------------------
       よく使う state ヘルパ
       --------------------------- */

    /** 売却済みにする */
    public function sold(): static
    {
        return $this->state(fn () => ['is_sold' => true]);
    }

    /** ダミー画像パスを入れる */
    public function withImage(): static
    {
        return $this->state(fn () => ['image_path' => 'products/sample.jpg']);
    }

    /** 特定ユーザーの出品として作成 */
    public function byUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    /** 単一カテゴリ（belongsTo）を同時作成して紐付け */
    public function withSingleCategory(?string $name = null): static
    {
        return $this->state(function () use ($name) {
            // Factory をそのまま返すと id が自動で入る（belongsTo）
            return [
                'category_id' => $name
                    ? Category::factory()->create(['name' => $name])->id
                    : Category::factory(),
            ];
        });
    }

    /**
     * 多対多カテゴリ（belongsToMany）を名前配列で付与
     * 例) ->withCategories(['メンズ','シューズ'])
     */
    public function withCategories(array $names): static
    {
        // ✔ Laravel の afterCreating は $model だけを受け取る形にしておく（第2引数を使わない）
        return $this->afterCreating(function (Product $product) use ($names) {
            if (!method_exists($product, 'categories')) {
                return;
            }
            $ids = [];
            foreach ($names as $name) {
                if ($name instanceof Category) {
                    $ids[] = $name->id;
                } elseif (is_string($name) && $name !== '') {
                    $ids[] = Category::factory()->create(['name' => $name])->id;
                }
            }
            if ($ids) {
                $product->categories()->attach($ids);
            }
        });
    }

    /**
     * 多対多カテゴリ（belongsToMany）をダミー n 件付与
     * 例) ->withCategoryCount(2)
     */
    public function withCategoryCount(int $count): static
    {
        return $this->afterCreating(function (Product $product) use ($count) {
            if (!method_exists($product, 'categories') || $count <= 0) {
                return;
            }
            $ids = Category::factory()->count($count)->create()->pluck('id')->all();
            if ($ids) {
                $product->categories()->attach($ids);
            }
        });
    }

    /** 価格帯ショートカット */
    public function cheap(): static
    {
        return $this->state(fn () => ['price' => $this->faker->numberBetween(500, 1999)]);
    }

    public function pricey(): static
    {
        return $this->state(fn () => ['price' => $this->faker->numberBetween(30000, 100000)]);
    }
}
