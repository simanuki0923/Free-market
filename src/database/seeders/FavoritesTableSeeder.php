<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Product;

class FavoritesTableSeeder extends Seeder
{
    public function run(): void
    {
        // 1) デモユーザーを確実に用意（存在すれば再利用）
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'デモ太郎', 'password' => Hash::make('password')]
        );

        // 2) お気に入りに入れる対象（販売中のみ上位から数件）
        $productIds = Product::where('is_sold', false)
            ->orderBy('id')
            ->limit(5)               // 好きな件数に調整可
            ->pluck('id');

        if ($productIds->isEmpty()) {
            // 商品が無い場合は何もしない（ProductsTableSeeder を先に実行してね）
            return;
        }

        // 3) favorites（user_id, product_id）はユニーク制約あり → updateOrInsert で重複回避
        foreach ($productIds as $pid) {
            DB::table('favorites')->updateOrInsert(
                ['user_id' => $user->id, 'product_id' => $pid],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
