<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Profile;
use App\Models\Product;
use Illuminate\Support\Arr;
use App\Http\Requests\AddressRequest;

class PurchaseAddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** 住所編集フォーム表示 */
    public function edit(Request $request)
    {
        // ?item_id= でも /purchase/address/{item_id} でも受ける
        $itemId = (int) ($request->query('item_id') ?? $request->route('item_id'));

        if ($itemId <= 0) {
            return redirect()->route('item')->with('error', '商品が指定されていません。');
        }

        // プロフィールが無ければ空で作成
        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);

        return view('address', [
            'profile' => $profile,
            'item_id' => $itemId, // ← hidden で埋め込む
        ]);
    }

    /** 住所更新 → 購入画面へ戻る */
    public function update(AddressRequest $request)
    {
        // バリデーション済みデータ
        $validated = $request->validated();

        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);

        // プロフィールに関係あるキーだけ反映（item_id は除外）
        $profile->fill(Arr::only($validated, ['postal_code','address1','address2','phone']));
        $profile->save();

        return redirect()
            ->route('purchase', ['item_id' => $validated['item_id']])
            ->with('success', '住所情報を更新しました。');
    }
}
