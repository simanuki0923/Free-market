<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Comment;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only(['store', 'storeComment']);
    }

    public function store(Request $request, int $item_id): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        return $this->storeCore($request, $item_id);
    }

    public function storeComment(Request $request, int $item_id): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        return $this->storeCore($request, $item_id);
    }

    private function storeCore(Request $request, int $item_id)
    {
        $request->validate([
            'body' => ['required','string','max:255'],
        ]);

        $product = Product::findOrFail($item_id);

        $comment = Comment::create([
            'user_id'    => Auth::id(),
            'product_id' => $product->id,
            'body'       => $request->string('body'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok'      => true,
                'message' => 'コメントを投稿しました。',
                'comment' => [
                    'id'      => $comment->id,
                    'body'    => $comment->body,
                    'user_id' => $comment->user_id,
                ],
            ]);
        }
        return back()->with('success', 'コメントを投稿しました。');
    }
}
