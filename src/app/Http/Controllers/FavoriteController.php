<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Favorite;

class FavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, int $item_id): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $product = Product::findOrFail($item_id);

        Favorite::firstOrCreate([
            'user_id'    => Auth::id(),
            'product_id' => $product->id,
        ]);

        return $this->respond($request, true, 'マイリストに追加しました。');
    }

    public function destroy(Request $request, int $item_id): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $product = Product::findOrFail($item_id);

        Favorite::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->delete();

        return $this->respond($request, true, 'マイリストから削除しました。');
    }

    public function toggle(Request $request, int $item_id): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $product = Product::findOrFail($item_id);

        $fav = Favorite::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if ($fav) {
            $fav->delete();
            return $this->respond($request, true, 'マイリストから削除しました。', ['favorited' => false]);
        }

        Favorite::create([
            'user_id'    => Auth::id(),
            'product_id' => $product->id,
        ]);

        return $this->respond($request, true, 'マイリストに追加しました。', ['favorited' => true]);
    }

    private function respond(Request $request, bool $ok, string $message, array $extra = [])
    {
        if ($request->wantsJson()) {
            return response()->json(array_merge([
                'ok'      => $ok,
                'message' => $message,
            ], $extra));
        }
        return back()->with($ok ? 'success' : 'error', $message);
    }
}
