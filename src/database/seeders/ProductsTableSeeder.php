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
            // ------------------------------------------------------------
            // 1) デモユーザ作成（3ユーザのみ）
            // ------------------------------------------------------------
            // 1〜5の商品を出品するユーザ
            $sellerA = User::firstOrCreate(
                ['email' => 'demo-seller-a@example.com'],
                [
                    'name'              => 'デモ出品者A（1-5）',
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            // 6〜10の商品を出品するユーザ
            $sellerB = User::firstOrCreate(
                ['email' => 'demo-seller-b@example.com'],
                [
                    'name'              => 'デモ出品者B（6-10）',
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            // 3人目ユーザ（購入者としてチャット確認に使用）
            // ※ もともとの「未紐づけ」ユーザを購入者兼用にする
            $buyerUser = User::firstOrCreate(
                ['email' => 'demo-idle-user@example.com'],
                [
                    'name'              => 'デモ未紐づけユーザ（購入確認兼用）',
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            // ------------------------------------------------------------
            // 2) カテゴリ準備
            // ------------------------------------------------------------
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

            // ------------------------------------------------------------
            // 3) 商品データ（ダミーユーザ作成.txt の1〜10）
            // ------------------------------------------------------------
            // [番号, 商品名, 価格, ブランド, 説明, 画像URL, 状態]
            $rows = [
                [1, '腕時計',           15000, 'Rolax',     'スタイリッシュなデザインのメンズ腕時計', 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Armani+Mens+Clock.jpg', '良好'],
                [2, 'HDD',               5000, '西芝',      '高速で信頼性の高いハードディスク',       'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/HDD+Hard+Disk.jpg', '目立った傷や汚れなし'],
                [3, '玉ねぎ3束',          300, null,        '新鮮な玉ねぎ3束のセット',                 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/iLoveIMG+d.jpg', 'やや傷や汚れあり'],
                [4, '革靴',              4000, null,        'クラシックなデザインの革靴',               'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Leather+Shoes+Product+Photo.jpg', '状態が悪い'],
                [5, 'ノートPC',         45000, null,        '高性能なノートパソコン',                   'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Living+Room+Laptop.jpg', '良好'],
                [6, 'マイク',            8000, null,        '高音質のレコーディング用マイク',           'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Music+Mic+4632231.jpg', '目立った傷や汚れなし'],
                [7, 'ショルダーバッグ',   3500, null,       'おしゃれなショルダーバッグ',               'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Purse+fashion+pocket.jpg', 'やや傷や汚れあり'],
                [8, 'タンブラー',         500, null,        '使いやすいタンブラー',                     'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Tumbler+souvenir.jpg', '状態が悪い'],
                [9, 'コーヒーミル',      4000, 'Starbacks', '手動のコーヒーミル',                       'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Waitress+with+Coffee+Grinder.jpg', '良好'],
                [10, 'メイクセット',     2500, null,        '便利なメイクアップセット',                 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/%E5%A4%96%E5%87%BA%E3%83%A1%E3%82%A4%E3%82%AF%E3%82%A2%E3%83%83%E3%83%95%E3%82%9A%E3%82%BB%E3%83%83%E3%83%88.jpg', '目立った傷や汚れなし'],
            ];

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

            // ------------------------------------------------------------
            // 4) Product / Sell 作成
            // ------------------------------------------------------------
            $createdSellIdsForChat = [];

            foreach ($rows as [$seq, $name, $price, $brand, $description, $imageUrl, $condition]) {
                // 1〜5は sellerA、6〜10は sellerB
                $seller = ($seq <= 5) ? $sellerA : $sellerB;

                // チャット確認用に売却済みを避けるため、今回は全部 false にしておく
                $isSold = false;

                $categoryId = $guessCategoryId($name);

                $product = Product::updateOrCreate(
                    [
                        'user_id' => $seller->id,
                        'name'    => $name,
                    ],
                    [
                        'category_id' => $categoryId,
                        'brand'       => $brand,
                        'price'       => $price,
                        'image_path'  => $imageUrl,
                        'condition'   => $condition,
                        'description' => $description,
                        'is_sold'     => $isSold,
                    ]
                );

                $sell = Sell::updateOrCreate(
                    [
                        'product_id' => $product->id,
                    ],
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

                // チャット用取引を作る対象（1,2,6,7の商品）を記録
                if (in_array($seq, [1, 2, 6, 7], true)) {
                    $createdSellIdsForChat[] = $sell->id;
                }
            }

            // ------------------------------------------------------------
            // 5) チャット動作確認用の Purchase / Transaction を作成
            // ------------------------------------------------------------
            // ルートは /chat/buyer/{transaction}, /chat/seller/{transaction}
            // transaction が無いと 404 になるため、ここで用意する
            if (Schema::hasTable('purchases') && Schema::hasTable('transactions')) {
                $targetSells = Sell::query()
                    ->whereIn('id', $createdSellIdsForChat)
                    ->orderBy('id')
                    ->get();

                foreach ($targetSells as $sell) {
                    // Purchase（購入者は3人目ユーザを利用）
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

                    // Transaction（purchase_id単位で一意想定）
                    Transaction::firstOrCreate(
                        [
                            'purchase_id' => $purchase->id,
                        ],
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

            // ------------------------------------------------------------
            // 6) （任意）チャットメッセージのダミー作成
            // ------------------------------------------------------------
            // プロジェクト側に chat_messages テーブル/モデルが存在する場合だけ作成
            // ※ カラム構成が環境差分で違う可能性があるため、最低限の安全な条件分岐にしています
            if (Schema::hasTable('chat_messages')) {
                $transactions = Schema::hasTable('transactions')
                    ? DB::table('transactions')->select('id', 'buyer_id', 'seller_id')->orderBy('id')->get()
                    : collect();

                foreach ($transactions as $tx) {
                    // 既にメッセージがあるならスキップ
                    $exists = DB::table('chat_messages')
                        ->where('transaction_id', $tx->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $columns = Schema::getColumnListing('chat_messages');

                    $row1 = [];
                    $row2 = [];

                    // 必須想定カラムを柔軟に埋める
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

                    // bodyがない構成だとinsert失敗しやすいので、最低限 body がある時だけ投入
                    if (array_key_exists('body', $row1)) {
                        DB::table('chat_messages')->insert([$row1, $row2]);

                        // transactions.last_message_at がある場合は更新
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