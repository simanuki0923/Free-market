<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            '家電','メンズ','レディース','シューズ','家具','ゲーム','本','おもちゃ','スポーツ'
        ]);

        $slug = Str::slug($name).'-'.Str::random(6);

        return [
            'name' => $name,
            'slug' => $slug,
        ];
    }
}
