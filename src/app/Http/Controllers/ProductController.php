<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function show(int $item_id)
    {
        $product = Product::with([
                'category',
                'sell',
                'comments.user.profile',
            ])
            ->withCount(['favorites','comments'])
            ->findOrFail($item_id);

        $isFavorited = Auth::check()
            ? $product->favorites()->where('user_id', Auth::id())->exists()
            : false;

        return view('product', compact('product', 'isFavorited'));
    }
}
