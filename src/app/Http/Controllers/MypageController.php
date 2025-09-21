<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class MypageController extends Controller
{
    /**
     * マイページ（出品した商品／購入した商品を表示）
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 出品した商品
        $listedProducts = Product::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->get(['id', 'name', 'image_url', 'is_sold']);

        // 購入した商品（スキーマ2パターン対応）
        $purchasedProducts = collect();

        // ① purchases / purchase_items 方式
        if (
            DB::getSchemaBuilder()->hasTable('purchases') &&
            DB::getSchemaBuilder()->hasTable('purchase_items') &&
            DB::getSchemaBuilder()->hasColumn('purchases', 'user_id') &&
            DB::getSchemaBuilder()->hasColumn('purchase_items', 'product_id')
        ) {
            $purchasedProducts = Product::query()
                ->join('purchase_items', 'purchase_items.product_id', '=', 'products.id')
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->where('purchases.user_id', $user->id)
                ->orderByDesc('products.id')
                ->get(['products.id', 'products.name', 'products.image_url', 'products.is_sold']);

        // ② products に buyer_id がある方式
        } elseif (DB::getSchemaBuilder()->hasColumn('products', 'buyer_id')) {
            $purchasedProducts = Product::query()
                ->where('buyer_id', $user->id)
                ->latest('id')
                ->get(['id', 'name', 'image_url', 'is_sold']);
        }

        return view('mypage', [
            'user'               => $user,
            'user_name'          => $user->name,
            'listedProducts'     => $listedProducts,
            'purchasedProducts'  => $purchasedProducts,
        ]);
    }
}
