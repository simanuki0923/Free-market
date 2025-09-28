<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sell;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SellController extends Controller
{
    /**
     * 出品フォーム表示
     * - fallbackカテゴリをDBに upsert 後、常に id/name の形で Blade に渡す
     * - 状態セレクトは Sell::CONDITION_LABELS を使用
     */
    public function create()
    {
        // UIに常に出したい既定カテゴリ（存在しなければ作る）
        $fallback = [
            'ファッション','家電','インテリア','レディース','メンズ','コスメ',
            '本','ゲーム','スポーツ','キッチン','ハンドメイド','アクセサリー',
            'おもちゃ','ベビー・キッズ',
        ];

        foreach ($fallback as $name) {
            Category::firstOrCreate(['name' => $name]); // slugがある場合はモデル側でnullableに
        }

        // ここで取得すれば、必ず id / name が揃っている
        $categories = Category::orderBy('id')->get(['id','name']);

        return view('sell', [
            'categories'    => $categories,                 // ★ Blade は $categories を参照
            'conditionList' => Sell::CONDITION_LABELS,      // 表示用ラベル
        ]);
    }

    /**
     * 出品保存
     * - Sell を作成
     * - Product を作成し、sell.product_id を更新
     * - カテゴリは Product に sync（必要なら Sell 側にも同期）
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'image'        => ['nullable','image','mimes:jpg,jpeg,png,gif,webp','max:5120'],
            'categories'   => ['required','array','min:1'],
            'categories.*' => ['filled'],
            // condition は日本語 or 数値どちらでも入る想定（モデルで正規化）。無効値は後で弾く
            'condition'    => ['required','string','max:50'],
            'name'         => ['required','string','max:100'],
            'brand'        => ['nullable','string','max:100'],
            'description'  => ['required','string','max:2000'],
            'price'        => ['required','integer','min:1'],
        ]);

        // condition の妥当性チェック（正規化できない値はエラーに）
        $normalized = Sell::normalizeCondition($data['condition']);
        if ($normalized === null) {
            return back()
                ->withErrors(['condition' => '商品の状態の選択が不正です。'])
                ->withInput();
        }

        $user = Auth::user();

        DB::transaction(function () use ($request, $data, $user, $normalized) {
            // 画像保存（storage/app/public/products）
            $imagePath = $request->hasFile('image')
                ? $request->file('image')->store('products', 'public')
                : null;

            // 1) Sell を作成（モデルの setConditionAttribute でも数値化されるが、ここでは確定値を渡す）
            $sell = Sell::create([
                'user_id'     => $user->id,
                'name'        => $data['name'],
                'brand'       => $data['brand'] ?? null,
                'price'       => $data['price'],
                'image_path'  => $imagePath,
                'condition'   => $normalized,            // 数値1..6
                'description' => $data['description'],
                'is_sold'     => false,
            ]);

            // 2) Product を作成（一覧/詳細は基本 Product 起点）
            $product = Product::create([
                'user_id'     => $user->id,
                'name'        => $sell->name,
                'brand'       => $sell->brand,
                'price'       => $sell->price,
                // product.blade.php で storage パスを asset('storage/..') に変換している想定
                'image_url'   => $sell->image_path,
                'description' => $sell->description,
                'is_sold'     => false,
            ]);

            // 3) Sell ⇔ Product の関連
            $sell->product_id = $product->id;
            $sell->save();

            // 4) カテゴリ同期（id / name どちらでも受け付け）
            $categoryIds = [];
            foreach ($data['categories'] as $c) {
                if (is_numeric($c)) {
                    $id = (int)$c;
                    if (Category::whereKey($id)->exists()) {
                        $categoryIds[] = $id;
                    }
                } else {
                    $categoryIds[] = Category::firstOrCreate(['name' => (string)$c])->id;
                }
            }
            if ($categoryIds) {
                // 一覧/検索が Product 起点のため、まず Product に付与
                $product->categories()->sync($categoryIds);
                // Sell 側にも保持したい場合は下行を有効化（任意）
                $sell->categories()->sync($categoryIds);
            }
        });

        return redirect()->route('item')->with('status', '出品を保存しました');
    }
}
