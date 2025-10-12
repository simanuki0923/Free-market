<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id'    => User::factory(),
            'body'       => $this->faker->sentence(),
        ];
    }
}
