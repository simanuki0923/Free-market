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
        // 一意な名前と必須の slug を同時に生成
        $name = $this->faker->unique()->randomElement([
            '家電','メンズ','レディース','シューズ','家具','ゲーム','本','おもちゃ','スポーツ'
        ]);

        // SQLite 等で一意制約衝突を避けるために乱数サフィックスを付与
        $slug = Str::slug($name).'-'.Str::random(6);

        return [
            'name' => $name,
            'slug' => $slug,
        ];
    }
}
