<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class MypageController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $user->load('profile');

        $displayName = $user->profile->display_name ?? $user->name ?? 'ユーザー';

        // 出品した商品（そのまま）
        $listedProducts = Product::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->get(['id','name','image_url','is_sold']);

        // ===== 購入した商品（どんなスキーマでも拾うフォールバック付き） =====
        $schema = DB::getSchemaBuilder();

        $hasPurchases       = $schema->hasTable('purchases');
        $hasPurchaseItems   = $schema->hasTable('purchase_items');
        $hasProductsBuyerId = $schema->hasColumn('products', 'buyer_id');
        $hasPurchasesUserId = $hasPurchases && $schema->hasColumn('purchases', 'user_id');
        $hasPurchasesProdId = $hasPurchases && $schema->hasColumn('purchases', 'product_id');
        $hasPurchasedAtCol  = $hasPurchases && $schema->hasColumn('purchases', 'purchased_at');
        $hasStatusCol       = $hasPurchases && $schema->hasColumn('purchases', 'status');

        // 1) purchases + purchase_items 方式（最優先）
        if ($hasPurchases && $hasPurchaseItems && $hasPurchasesUserId && $schema->hasColumn('purchase_items','product_id')) {
            $selects = [
                'products.id',
                'products.name',
                'products.image_url',
                'products.is_sold',
            ];
            // 列がある場合のみ追加
            if ($hasStatusCol)      { $selects[] = DB::raw('purchases.status as purchase_status'); }
            if ($hasPurchasedAtCol) { $selects[] = DB::raw('purchases.purchased_at as purchased_at'); }
            else                    { $selects[] = DB::raw('purchases.created_at  as purchased_at'); }

            $purchasedProducts = Product::query()
                ->join('purchase_items', 'purchase_items.product_id', '=', 'products.id')
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->where('purchases.user_id', $user->id)
                ->orderByDesc('products.id')
                ->get($selects);

        // 2) purchases に product_id がある方式
        } elseif ($hasPurchases && $hasPurchasesUserId && $hasPurchasesProdId) {
            $selects = [
                'products.id',
                'products.name',
                'products.image_url',
                'products.is_sold',
            ];
            if ($hasStatusCol)      { $selects[] = DB::raw('purchases.status as purchase_status'); }
            if ($hasPurchasedAtCol) { $selects[] = DB::raw('purchases.purchased_at as purchased_at'); }
            else                    { $selects[] = DB::raw('purchases.created_at  as purchased_at'); }

            $purchasedProducts = Product::query()
                ->join('purchases', 'purchases.product_id', '=', 'products.id')
                ->where('purchases.user_id', $user->id)
                ->orderByDesc('products.id')
                ->get($selects);

        // 3) products に buyer_id がある方式（旧実装）
        } elseif ($hasProductsBuyerId) {
            $purchasedProducts = Product::query()
                ->where('buyer_id', $user->id)
                ->latest('id')
                ->get(['id','name','image_url','is_sold']);

        // 4) どれにも当てはまらない → 空
        } else {
            $purchasedProducts = collect();
        }

        return view('mypage', [
            'user'              => $user,
            'displayName'       => $displayName,
            'listedProducts'    => $listedProducts,
            'purchasedProducts' => $purchasedProducts,
        ]);
    }
}
