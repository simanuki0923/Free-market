<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Product;
use App\Models\Sell;
use App\Models\Category;

class ProductsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $demo = User::firstOrCreate(
                ['email' => 'demo@example.com'],
                ['name' => 'デモ太郎', 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );
            $seller1 = User::firstOrCreate(
                ['email' => 'seller1@example.com'],
                ['name' => '販売者一郎', 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );
            $seller2 = User::firstOrCreate(
                ['email' => 'seller2@example.com'],
                ['name' => '販売者二郎', 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );

            $catMap = [
                '家電'       => ['ノートPC','マイク','USB','Bluetooth','HDD','卓上ライト'],
                'ファッション' => ['腕時計','革靴','ショルダーバッグ','メイク'],
                '食料品'     => ['玉ねぎ'],
                '生活雑貨'   => ['タンブラー','コーヒーミル'],
                'PC/周辺機器' => ['HDD','USB','ノートPC'],
                '美容'       => ['メイクセット'],
            ];
            $categories = [];
            foreach (array_keys($catMap) as $name) {
                $categories[$name] = Category::firstOrCreate(
                    ['slug' => \Str::slug($name, '-')],
                    ['name' => $name]
                );
            }

            $rows = [
                ['腕時計',15000,'Rolax','スタイリッシュなデザインのメンズ腕時計','https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Armani+Mens+Clock.jpg','良好'],
                ['HDD',5000,'西芝','高速で信頼性の高いハードディスク','https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/HDD+Hard+Disk.jpg','目立った傷や汚れなし'],
                ['玉ねぎ3束',300,null,'新鮮な玉ねぎ3束のセット','https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/iLoveIMG+d.jpg','やや傷や汚れあり'],
                ['革靴',4000,null,'クラシックなデザインの革靴','https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Leather+Shoes+Product+Photo.jpg','状態が悪い'],
                ['ノートPC',45000,null,'高性能なノートパソコン','https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Living+Room+Laptop.jpg','良好'],
                ['マイク',8000,null,'高音質のレコーディング用マイク','https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Music+Mic+4632231.jpg','目立った傷や汚れなし'],
                ['ショルダーバッグ',3500,null,'おしゃれなショルダーバッグ','https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Purse+fashion+pocket.jpg','やや傷や汚れあり'],
                ['タンブラー',500,null,'使いやすいタンブラー','https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Tumbler+souvenir.jpg','状態が悪い'],
                ['コーヒーミル',4000,'Starbacks','手動のコーヒーミル','https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Waitress+with+Coffee+Grinder.jpg','良好'],
                ['メイクセット',2500,null,'便利なメイクアップセット','https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/%E5%A4%96%E5%87%BA%E3%83%A1%E3%82%A4%E3%82%AF%E3%82%A2%E3%83%83%E3%83%95%E3%82%9A%E3%82%BB%E3%83%83%E3%83%88.jpg','目立った傷や汚れなし'],
            ];

            $guessCategoryId = function (string $name) use ($categories, $catMap): ?int {
                foreach ($catMap as $catName => $keys) {
                    foreach ($keys as $k) {
                        if (str_contains($name, $k)) {
                            return $categories[$catName]->id ?? null;
                        }
                    }
                }
                return null;
            };

            foreach ($rows as $i => [$name, $price, $brand, $desc, $img, $cond]) {
                $seller = ($i % 2 === 0) ? $seller1 : $seller2;
                $isSold = in_array($i, [2,5,8], true);

                $categoryId = $guessCategoryId($name);

                $product = Product::updateOrCreate(
                    ['user_id' => $seller->id, 'name' => $name],
                    [
                        'category_id' => $categoryId,
                        'brand'       => $brand,
                        'price'       => $price,
                        'description' => $desc,
                        'image_path'  => $img,
                        'condition'   => $cond,
                        'is_sold'     => $isSold,
                    ]
                );

                Sell::updateOrCreate(
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
            }

            $ownRows = [
                ['デモ用・卓上ライト', 1800, null, 'コンパクトな卓上ライト', 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Tumbler+souvenir.jpg', '良好', false],
                ['デモ用・Bluetoothイヤホン', 3200, null, 'ケース付きワイヤレスイヤホン', 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Music+Mic+4632231.jpg', '目立った傷や汚れなし', true],
                ['デモ用・USBハブ', 900, null, '4ポートUSBハブ', 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/HDD+Hard+Disk.jpg', 'やや傷や汚れあり', false],
            ];

            foreach ($ownRows as [$name, $price, $brand, $desc, $img, $cond, $isSold]) {
                $categoryId = $guessCategoryId($name);

                $product = Product::updateOrCreate(
                    ['user_id' => $demo->id, 'name' => $name],
                    [
                        'category_id' => $categoryId,
                        'brand'       => $brand,
                        'price'       => $price,
                        'description' => $desc,
                        'image_path'  => $img,
                        'condition'   => $cond,
                        'is_sold'     => $isSold,
                    ]
                );

                Sell::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'user_id'     => $demo->id,
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
            }
        });
    }
}
