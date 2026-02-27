<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Product;
use App\Models\Sell;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\Transaction;

class ProductsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $sellerA = User::firstOrCreate(
                ['email' => 'demo-seller-a@example.com'],
                [
                    'name'              => 'デモ出品者A（CO01-CO05）',
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $sellerB = User::firstOrCreate(
                ['email' => 'demo-seller-b@example.com'],
                [
                    'name'              => 'デモ出品者B（CO06-CO10）',
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $buyerUser = User::firstOrCreate(
                ['email' => 'demo-idle-user@example.com'],
                [
                    'name'              => 'デモ未紐づけユーザ（購入確認兼用）',
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $catMap = [
                '家電'         => ['ノートPC', 'マイク', 'HDD'],
                'ファッション' => ['腕時計', '革靴', 'ショルダーバッグ'],
                '食料品'       => ['玉ねぎ'],
                '生活雑貨'     => ['タンブラー', 'コーヒーミル'],
                '美容'         => ['メイクセット'],
                'PC/周辺機器'  => ['HDD', 'ノートPC'],
            ];

            $categories = [];
            foreach (array_keys($catMap) as $name) {
                $categories[$name] = Category::firstOrCreate(
                    ['slug' => Str::slug($name, '-')],
                    ['name' => $name]
                );
            }

            $guessCategoryId = function (string $productName) use ($catMap, $categories): ?int {
                foreach ($catMap as $categoryName => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (str_contains($productName, $keyword)) {
                            return $categories[$categoryName]->id ?? null;
                        }
                    }
                }
                return null;
            };

            $rows = [
                [1,  '腕時計',           15000, 'Rolax',     'スタイリッシュなデザインのメンズ腕時計', 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Armani+Mens+Clock.jpg', '良好'],
                [2,  'HDD',               5000, '西芝',      '高速で信頼性の高いハードディスク',       'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/HDD+Hard+Disk.jpg', '目立った傷や汚れなし'],
                [3,  '玉ねぎ3束',          300, null,        '新鮮な玉ねぎ3束のセット',                 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/iLoveIMG+d.jpg', 'やや傷や汚れあり'],
                [4,  '革靴',              4000, null,        'クラシックなデザインの革靴',               'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Leather+Shoes+Product+Photo.jpg', '状態が悪い'],
                [5,  'ノートPC',         45000, null,        '高性能なノートパソコン',                   'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Living+Room+Laptop.jpg', '良好'],
                [6,  'マイク',            8000, null,        '高音質のレコーディング用マイク',           'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Music+Mic+4632231.jpg', '目立った傷や汚れなし'],
                [7,  'ショルダーバッグ',   3500, null,       'おしゃれなショルダーバッグ',               'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Purse+fashion+pocket.jpg', 'やや傷や汚れあり'],
                [8,  'タンブラー',         500, null,        '使いやすいタンブラー',                     'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Tumbler+souvenir.jpg', '状態が悪い'],
                [9,  'コーヒーミル',      4000, 'Starbacks', '手動のコーヒーミル',                       'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Waitress+with+Coffee+Grinder.jpg', '良好'],
                [10, 'メイクセット',     2500, null,        '便利なメイクアップセット',                 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/%E5%A4%96%E5%87%BA%E3%83%A1%E3%82%A4%E3%82%AF%E3%82%A2%E3%83%83%E3%83%95%E3%82%9A%E3%82%BB%E3%83%83%E3%83%88.jpg', '目立った傷や汚れなし'],
            ];

            $createdSellIdsForChat = [];

            foreach ($rows as [$seq, $name, $price, $brand, $description, $imageUrl, $condition]) {

                $seller     = ($seq <= 5) ? $sellerA : $sellerB;
                $categoryId = $guessCategoryId($name);
                $isSold     = false;

                DB::table('products')->updateOrInsert(
                    ['id' => $seq],
                    [
                        'user_id'      => $seller->id,
                        'category_id'  => $categoryId,
                        'name'         => $name,
                        'brand'        => $brand,
                        'price'        => $price,
                        'image_path'   => $imageUrl,
                        'condition'    => $condition,
                        'description'  => $description,
                        'is_sold'      => $isSold,
                        'category_ids_json' => null,
                        'updated_at'   => now(),
                        'created_at'   => now(),
                    ]
                );

                $product = Product::query()->findOrFail($seq);

                $sell = Sell::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'user_id'     => $seller->id,
                        'category_id' => $product->category_id,
                        'name'        => $product->name,
                        'brand'       => $product->brand,
                        'price'       => $product->price,
                        'image_path'  => $product->image_path,
                        'condition'   => $product->condition,
                        'description' => $product->description,
                        'is_sold'     => $isSold,
                    ]
                );

                if (in_array($seq, [1, 2, 6, 7], true)) {
                    $createdSellIdsForChat[] = $sell->id;
                }
            }

            if (Schema::hasTable('purchases') && Schema::hasTable('transactions')) {

                $targetSells = Sell::query()
                    ->whereIn('id', $createdSellIdsForChat)
                    ->orderBy('id')
                    ->get();

                foreach ($targetSells as $sell) {

                    $purchase = Purchase::firstOrCreate(
                        [
                            'user_id' => $buyerUser->id,
                            'sell_id' => $sell->id,
                        ],
                        [
                            'amount'         => (int) ($sell->price ?? 0),
                            'payment_method' => 'credit_card',
                            'purchased_at'   => now(),
                        ]
                    );

                    Transaction::firstOrCreate(
                        ['purchase_id' => $purchase->id],
                        [
                            'sell_id'         => $sell->id,
                            'product_id'      => $sell->product_id,
                            'seller_id'       => $sell->user_id,
                            'buyer_id'        => $buyerUser->id,
                            'status'          => 'ongoing',
                            'last_message_at' => now(),
                        ]
                    );
                }
            }

            if (Schema::hasTable('chat_messages')) {

                $transactions = Schema::hasTable('transactions')
                    ? DB::table('transactions')->select('id', 'buyer_id', 'seller_id')->orderBy('id')->get()
                    : collect();

                foreach ($transactions as $tx) {

                    $exists = DB::table('chat_messages')
                        ->where('transaction_id', $tx->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $columns = Schema::getColumnListing('chat_messages');

                    $row1 = [];
                    $row2 = [];

                    if (in_array('transaction_id', $columns, true)) {
                        $row1['transaction_id'] = $tx->id;
                        $row2['transaction_id'] = $tx->id;
                    }
                    if (in_array('user_id', $columns, true)) {
                        $row1['user_id'] = $tx->buyer_id;
                        $row2['user_id'] = $tx->seller_id;
                    }
                    if (in_array('body', $columns, true)) {
                        $row1['body'] = 'はじめまして。購入しました。よろしくお願いします。';
                        $row2['body'] = 'ご購入ありがとうございます。発送準備を進めます。';
                    }
                    if (in_array('image_path', $columns, true)) {
                        $row1['image_path'] = null;
                        $row2['image_path'] = null;
                    }
                    if (in_array('created_at', $columns, true)) {
                        $row1['created_at'] = now();
                        $row2['created_at'] = now();
                    }
                    if (in_array('updated_at', $columns, true)) {
                        $row1['updated_at'] = now();
                        $row2['updated_at'] = now();
                    }

                    if (array_key_exists('body', $row1)) {
                        DB::table('chat_messages')->insert([$row1, $row2]);

                        if (Schema::hasColumn('transactions', 'last_message_at')) {
                            DB::table('transactions')
                                ->where('id', $tx->id)
                                ->update(['last_message_at' => now()]);
                        }
                    }
                }
            }
        });
    }
}