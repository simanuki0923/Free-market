<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, int $item_id)
    {
        $request->validate([
            'body' => ['required','string','max:2000'],
        ]);

        $product = Product::findOrFail($item_id);

        Comment::create([
            'product_id' => $product->id,
            'user_id'    => Auth::id(),
            'body'       => $request->body,
        ]);

        return back()->with('success','コメントを投稿しました。');
    }
}
