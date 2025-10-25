<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExhibitionRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Sell;
use App\Models\Product;
use App\Models\Category;

class SellController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create()
    {
        $categories = Category::orderBy('id')->get();

        return view('sell', [
            'categories' => $categories,
        ]);
    }

    public function store(ExhibitionRequest $request)
    {
        $validated = $request->validated();
        $userId = Auth::id();
        $allCategoryIds = $request->input('categories', []);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;

        DB::transaction(function () use ($validated, $userId, $imagePath, $allCategoryIds) {
            $product = Product::create([
                'user_id'           => $userId,
                'category_id'       => $validated['category_id'] ?? null,
                'name'              => $validated['name'],
                'brand'             => $validated['brand'] ?? null,
                'price'             => $validated['price'],
                'image_path'        => $imagePath,
                'condition'         => $validated['condition'] ?? null,
                'description'       => $validated['description'] ?? null,
                'is_sold'           => false,
                'category_ids_json' => $allCategoryIds,
            ]);

            Sell::create([
                'user_id'           => $userId,
                'product_id'        => $product->id,
                'category_id'       => $product->category_id,
                'name'              => $product->name,
                'brand'             => $product->brand,
                'price'             => $product->price,
                'image_path'        => $product->image_path,
                'condition'         => $product->condition,
                'description'       => $product->description,
                'is_sold'           => false,
                'category_ids_json' => $allCategoryIds,
            ]);
        });

        return redirect()
            ->route('item')
            ->with('success', '出品しました。');
    }
}
