<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 24;

        $rawTab = strtolower((string)$request->query('tab', 'all'));
        $tab = in_array($rawTab, ['all', 'mylist'], true) ? $rawTab : 'all';

        $page = (int) $request->query('page', 1);
        $isLoggedIn = Auth::check();

        if ($tab === 'mylist') {
            if (!$isLoggedIn) {
                $products = new LengthAwarePaginator(
                    collect(),
                    0,
                    $perPage,
                    $page,
                    [
                        'path'  => url()->current(),
                        'query' => array_merge($request->query(), ['tab' => 'mylist']),
                    ]
                );
                $products->setPageName('page');
            } else {
                $products = Product::query()
                    ->select('products.*')
                    ->join('favorites', 'favorites.product_id', '=', 'products.id')
                    ->where('favorites.user_id', Auth::id())
                    ->orderByDesc('favorites.created_at')
                    ->paginate($perPage)
                    ->appends(array_merge($request->query(), ['tab' => 'mylist']));
            }
        } else {
            $query = Product::query()->latest('id');
            if ($isLoggedIn) {
                $query->where('user_id', '!=', Auth::id());
            }

            $products = $query
                ->paginate($perPage)
                ->appends(array_merge($request->query(), ['tab' => 'all']));
            $tab = 'all';
        }

        return view('item', [
            'products'     => $products,
            'tab'          => $tab,
            'isLoggedIn'   => $isLoggedIn,
        ]);
    }
}
