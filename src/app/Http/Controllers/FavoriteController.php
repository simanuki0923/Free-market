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
        // middleware() が動かない場合は親 Controller の継承を確認してください
        $this->middleware('auth');
    }

    /**
     * お気に入り追加（重複回避）
     */
    public function store(Request $request, int $item_id): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $product = Product::findOrFail($item_id);

        // 自分の商品をお気に入り禁止にしたい場合は有効化
        // if ($product->user_id === Auth::id()) {
        //     return $this->respond($request, false, '自分の商品はマイリストに追加できません。');
        // }

        Favorite::firstOrCreate([
            'user_id'    => Auth::id(),
            'product_id' => $product->id,
        ]);

        return $this->respond($request, true, 'マイリストに追加しました。');
    }

    /**
     * お気に入り削除（存在しなくてもOK）
     */
    public function destroy(Request $request, int $item_id): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $product = Product::findOrFail($item_id);

        Favorite::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->delete();

        return $this->respond($request, true, 'マイリストから削除しました。');
    }

    /**
     * 1ボタンでお気に入り ON/OFF を切り替え
     */
    public function toggle(Request $request, int $item_id): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $product = Product::findOrFail($item_id);

        // if ($product->user_id === Auth::id()) {
        //     return $this->respond($request, false, '自分の商品はマイリストに追加できません。');
        // }

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

    /**
     * フォーム/JS 双方に対応した共通レスポンス
     */
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
