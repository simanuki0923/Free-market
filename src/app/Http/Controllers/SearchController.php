<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search', $request->string('q'))->toString();

        $query = Product::query();

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'LIKE', $like)
                  ->orWhere('brand', 'LIKE', $like)
                  ->orWhere('description', 'LIKE', $like);
            });
        }

        $products = $query->latest()->paginate(20)->appends($request->query());

        return view('search', [
            'products' => $products,
            'search'   => $search,
        ]);
    }
}
