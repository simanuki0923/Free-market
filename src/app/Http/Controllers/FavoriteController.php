<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class FavoriteControllzer extends Controller
{
    /**
     * お気に入りのトグル（追加/削除）
     * 中間テーブル: favorites（user_id, product_id）
     */
    public function toggle(Request $request, Product $product)
    {
        $userId    = Auth::id();
        $productId = $product->id;

        // 既に登録済みかチェック
        $exists = DB::table('favorites')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            // 解除
            DB::table('favorites')
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();
        } else {
            // 追加（unique(user_id, product_id)推奨）
            DB::table('favorites')->insert([
                'user_id'    => $userId,
                'product_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 元のページへ（?tab などのクエリも維持）
        return back();
    }
}
