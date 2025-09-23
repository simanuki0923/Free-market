<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProductsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // 1) ユーザー準備（存在すれば再利用）
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

            // 2) デモ商品の元データ（質問で共有いただいた10件）
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

            // 3) おすすめ側に出すための「他人の出品」を投入（seller1 / seller2）
            //    → 偶数は seller1、奇数は seller2 に分配
            //    → いくつかを is_sold = true にする（Sold 表示の確認用）
            foreach ($rows as $i => [$name, $price, $brand, $desc, $img, $cond]) {
                $seller = ($i % 2 === 0) ? $seller1 : $seller2;

                // index を見て Sold/未Sold を割り振り
                // 例: 2,5,8 番目を Sold にする
                $isSold = in_array($i, [2,5,8], true);

                Product::updateOrCreate(
                    ['user_id' => $seller->id, 'name' => $name], // (user, name) をユニークキーに
                    [
                        'brand'       => $brand,
                        'price'       => $price,
                        'description' => $desc,
                        'image_url'   => $img,
                        'condition'   => $cond,
                        'is_sold'     => $isSold,  // ← Sold を混在
                    ]
                );
            }

            // 4) デモユーザー自身の出品（おすすめでは除外される想定）
            //    除外ロジック・Sold 表示の動作確認用に 3件（うち1件 Sold）
            $ownRows = [
                ['デモ用・卓上ライト', 1800, null, 'コンパクトな卓上ライト', 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Tumbler+souvenir.jpg', '良好', false],
                ['デモ用・Bluetoothイヤホン', 3200, null, 'ケース付きワイヤレスイヤホン', 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Music+Mic+4632231.jpg', '目立った傷や汚れなし', true], // Sold
                ['デモ用・USBハブ', 900, null, '4ポートUSBハブ', 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/HDD+Hard+Disk.jpg', 'やや傷や汚れあり', false],
            ];
            foreach ($ownRows as [$name, $price, $brand, $desc, $img, $cond, $isSold]) {
                Product::updateOrCreate(
                    ['user_id' => $demo->id, 'name' => $name],
                    [
                        'brand'       => $brand,
                        'price'       => $price,
                        'description' => $desc,
                        'image_url'   => $img,
                        'condition'   => $cond,
                        'is_sold'     => $isSold,
                    ]
                );
            }
        });
    }
}
