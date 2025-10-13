<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Sell;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class MypageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_if(!$user, 403);

        $raw = strtolower((string) $request->query('page', 'sell'));
        $tab = in_array($raw, ['sell', 'buy'], true) ? $raw : 'sell';
        $perPage = 24;
        $mySells = Sell::query()
    ->where('user_id', $user->id)
    ->with(['product.category'])
    ->latest('id')
    ->paginate($perPage, ['*'], 'p1')
    ->appends(['page' => 'sell']);
        $purchasedProducts = Product::query()
    ->join('sells', 'sells.product_id', '=', 'products.id')
    ->join('purchases', 'purchases.sell_id', '=', 'sells.id')
    ->where('purchases.user_id', $user->id)
    ->orderByDesc('purchases.purchased_at')
    ->select([
        'products.id',
        'products.name',
        'products.image_path',
        'products.is_sold',
        'purchases.amount as purchased_amount',
        'purchases.purchased_at',
    ])
    ->paginate($perPage, ['*'], 'p2')
    ->appends(['page' => 'buy']);
        return view('mypage', [
            'user'               => $user,
            'page'               => $tab,
            'mySells'            => $mySells,
            'purchasedProducts'  => $purchasedProducts,
        ]);
    }
}
