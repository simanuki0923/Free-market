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
            'category_id' => null,
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

    public function sold(): static
    {
        return $this->state(fn () => ['is_sold' => true]);
    }

    public function withImage(): static
    {
        return $this->state(fn () => ['image_path' => 'products/sample.jpg']);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function withSingleCategory(?string $name = null): static
    {
        return $this->state(function () use ($name) {
            return [
                'category_id' => $name
                    ? Category::factory()->create(['name' => $name])->id
                    : Category::factory(),
            ];
        });
    }

    public function withCategories(array $names): static
    {
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

    public function cheap(): static
    {
        return $this->state(fn () => ['price' => $this->faker->numberBetween(500, 1999)]);
    }

    public function pricey(): static
    {
        return $this->state(fn () => ['price' => $this->faker->numberBetween(30000, 100000)]);
    }
}
