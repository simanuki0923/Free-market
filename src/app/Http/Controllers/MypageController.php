<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MypageController extends Controller
{
    /**
     * 商品一覧（未認証ユーザーにも表示）／マイリスト（認証時のみ）を返す
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        // 1) 全商品（ログイン時は自分の出品を除外）
        $productsQuery = Product::query()
            ->select(['id', 'user_id', 'name', 'image_url', 'is_sold'])
            ->latest('id');

        if ($userId) {
            $productsQuery->where('user_id', '!=', $userId);
        }
        $products = $productsQuery->get();

        // 2) マイリスト（認証時のみ）
        $myListProducts = collect();
        if ($userId) {
            $myListProducts = Product::query()
                ->select(['id', 'user_id', 'name', 'image_url', 'is_sold'])
                ->whereIn('id', function ($q) use ($userId) {
                    $q->from('favorites')
                      ->select('product_id')
                      ->where('user_id', $userId);
                })
                ->latest('id')
                ->get();
        }

        return view('mypage', [
            'products'       => $products,
            'myListProducts' => $myListProducts,
        ]);
    }
}

