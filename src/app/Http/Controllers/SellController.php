<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Sell;
use App\Models\Product;

class SellController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create()
    {
        return view('sell');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required','string','max:255'],
            'brand'       => ['nullable','string','max:255'],
            'price'       => ['required','integer','min:0'],
            'image'       => ['nullable','image','max:5120'],
            'condition'   => ['nullable','string','max:50'],
            'description' => ['nullable','string','max:10000'],
            'category_id' => ['nullable','integer','exists:categories,id'],
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;

        DB::transaction(function () use ($validated, $imagePath) {
            $product = Product::create([
                'user_id'     => Auth::id(),
                'category_id' => $validated['category_id'] ?? null,
                'name'        => $validated['name'],
                'brand'       => $validated['brand'] ?? null,
                'price'       => $validated['price'],
                'image_path'  => $imagePath,
                'condition'   => $validated['condition'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_sold'     => false,
            ]);

            Sell::create([
                'user_id'     => Auth::id(),
                'product_id'  => $product->id,
                'category_id' => $product->category_id,
                'name'        => $product->name,
                'brand'       => $product->brand,
                'price'       => $product->price,
                'image_path'  => $product->image_path,
                'condition'   => $product->condition,
                'description' => $product->description,
                'is_sold'     => false,
            ]);
        });

        return redirect()->route('item')->with('success', '出品しました。');
    }
}
