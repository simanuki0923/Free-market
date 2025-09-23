<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\User;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),             // 出品者
            'name'       => $this->faker->words(3, true),
            'brand'      => $this->faker->randomElement(['Apple','Sony','Canon','Panasonic','Nintendo','ASUS','Other']),
            'price'      => $this->faker->numberBetween(1000, 50000),
            'image_url'  => null,                        // storage を使うなら null のままでOK
            'is_sold'    => false,                       // 既定は未売
            'condition'  => $this->faker->randomElement(['new', 'like-new', 'used']),
            'description'=> $this->faker->sentence(12),
        ];
    }

    /**
     * 売却済み（Sold）にする state
     */
    public function sold(): self
    {
        return $this->state(fn () => ['is_sold' => true]);
    }
}
