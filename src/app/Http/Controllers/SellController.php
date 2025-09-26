<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Sell;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SellController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'        => ['required','string','max:255'],
            'brand'       => ['nullable','string','max:255'],
            'price'       => ['required','integer','min:0'],
            'image'       => ['nullable','image','max:5120'],
            'condition'   => ['nullable'],
            'description' => ['nullable','string','max:10000'],
            'categories'  => ['nullable','array'],
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;

        $cond = Sell::normalizeCondition($validated['condition'] ?? null);

        DB::transaction(function () use ($user, $validated, $imagePath, $cond, $request) {

            // 1) products を作成（詳細画面は products 基準）
            $product = Product::create([
                'user_id'    => $user->id,
                'name'       => $validated['name'],
                'brand'      => $validated['brand'] ?? null,
                'price'      => $validated['price'],
                'image_url'  => $imagePath,         // Product 側の画像列名に合わせる
                'is_sold'    => false,
                'description'=> $validated['description'] ?? null,
            ]);

            // 2) sells を作成（★ product_id が肝）
            $sell = Sell::create([
                'user_id'     => $user->id,
                'product_id'  => $product->id,      // ← これで mypage から正しく飛べる
                'name'        => $validated['name'],
                'brand'       => $validated['brand'] ?? null,
                'price'       => $validated['price'],
                'image_path'  => $imagePath,
                'condition'   => $cond,
                'description' => $validated['description'] ?? null,
                'is_sold'     => false,
            ]);

            // 3) カテゴリ同期（id配列 or name配列どちらでも対応）
            $categoryIds = [];
            foreach ((array) $request->input('categories', []) as $c) {
                if (is_numeric($c)) $categoryIds[] = (int)$c;
                elseif (is_string($c) && $c !== '') $categoryIds[] = Category::firstOrCreate(['name'=>$c])->id;
            }
            if ($categoryIds) {
                $sell->categories()->sync($categoryIds);
                // Product 側にもカテゴリを持たせる設計なら：$product->categories()->sync($categoryIds);
            }
        });

        return redirect()->route('mypage', ['page'=>'sell'])->with('status','出品を保存しました');
    }
}
