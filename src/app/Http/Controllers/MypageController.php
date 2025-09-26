<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Sell;
use App\Models\Product;

class MypageController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $page = $request->query('page', 'sell');

        // 出品（sells）— product を同時読み込み
        $mySells = Sell::byUser($user->id)
            ->with(['product','categories'])
            ->latest('id')
            ->paginate(24);

        // 購入（purchases 経由）
        $purchasedProducts = Product::query()
            ->join('purchases', 'purchases.product_id', '=', 'products.id')
            ->where('purchases.user_id', $user->id)
            ->orderByDesc('purchases.id')
            ->select([
                'products.id',
                'products.name',
                'products.image_url',
                'products.is_sold',
                'purchases.status as purchase_status',
                'purchases.price as purchased_price',
                'purchases.purchase_date',
            ])
            ->paginate(24, ['*'], 'p2');

        return view('mypage', compact('user','page','mySells','purchasedProducts'));
    }
}
