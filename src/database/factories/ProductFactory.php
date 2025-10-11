<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\User;
// Category を使うならコメントアウト解除
// use App\Models\Category;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),                          // 出品者
            'category_id' => null,                                     // ← マイグレが nullable なので既定は null
            'name'        => $this->faker->words(3, true),
            'brand'       => $this->faker->optional()->randomElement([
                'Apple','Sony','Canon','Panasonic','Nintendo','ASUS','Other'
            ]),                                                       // ← brand は nullable
            'price'       => $this->faker->numberBetween(1000, 50000), // ← unsignedInteger を満たす
            'image_path'  => null,                                     // ← カラム名を image_path に統一
            'condition'   => $this->faker->optional()->randomElement([
                'new', 'like-new', 'used'
            ]),                                                       // ← varchar(50) 範囲内
            'description' => $this->faker->optional()->sentence(12),
            'is_sold'     => false,                                    // 既定は未売
        ];
    }

    /**
     * 売却済み（Sold）にする state
     */
    public function sold(): static
    {
        return $this->state(fn () => ['is_sold' => true]);
    }

    /**
     * 画像あり（例：ストレージに置いたダミー画像パス）
     */
    public function withImage(): static
    {
        return $this->state(fn () => [
            // 例: storage:link 済みで public/storage/products/sample.jpg を想定
            'image_path' => 'products/sample.jpg',
        ]);
    }

    /**
     * 特定ユーザーの出品として作成
     */
    public function byUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    /**
     * カテゴリ紐付け（Category を使う場合）
     */
    public function withCategory(/* ?Category $category = null */): static
    {
        // Category モデルを使う場合は上部 use と下記コメントを外す
        // return $this->state(fn () => [
        //     'category_id' => $category?->id ?? Category::factory(),
        // ]);

        // Category をまだ作っていない場合はダミーIDや null を返す（ここでは null のまま）
        return $this->state(fn () => ['category_id' => null]);
    }

    /**
     * 価格帯のショートカット（任意）
     */
    public function cheap(): static
    {
        return $this->state(fn () => ['price' => $this->faker->numberBetween(500, 1999)]);
    }

    public function pricey(): static
    {
        return $this->state(fn () => ['price' => $this->faker->numberBetween(30000, 100000)]);
    }
}
