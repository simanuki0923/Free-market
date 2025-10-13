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
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'デモ太郎', 'password' => Hash::make('password')]
        );

        $productIds = Product::where('is_sold', false)
            ->orderBy('id')
            ->limit(5)
            ->pluck('id');

        if ($productIds->isEmpty()) {
            return;
        }

        foreach ($productIds as $pid) {
            DB::table('favorites')->updateOrInsert(
                ['user_id' => $user->id, 'product_id' => $pid],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
