<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'ファッション',    'slug' => 'fashion'],
            ['name' => '家電',           'slug' => 'electronics'],
            ['name' => 'インテリア',     'slug' => 'interior'],
            ['name' => 'レディース',     'slug' => 'ladies'],
            ['name' => 'メンズ',         'slug' => 'mens'],
            ['name' => 'コスメ',         'slug' => 'cosmetics'],
            ['name' => '本',             'slug' => 'books'],
            ['name' => 'ゲーム',         'slug' => 'game'],
            ['name' => 'スポーツ',       'slug' => 'sports'],
            ['name' => 'キッチン',       'slug' => 'kitchen'],
            ['name' => 'ハンドメイド',   'slug' => 'handmade'],
            ['name' => 'アクセサリー',   'slug' => 'accessory'],
            ['name' => 'おもちゃ',       'slug' => 'toys'],
            ['name' => 'ベビー・キッズ', 'slug' => 'baby-kids'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                ]
            );
        }
    }
}
