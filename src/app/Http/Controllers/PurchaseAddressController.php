<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Profile;
use App\Models\Product;
use Illuminate\Support\Arr;

class PurchaseAddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

   // app/Http/Controllers/PurchaseAddressController.php
public function edit(Request $request)
{
    // ?item_id= / /purchase/address/{item_id} のどちらでも受ける
    $itemId = (int) ($request->query('item_id') ?? $request->route('item_id'));

    if ($itemId <= 0) {
        // 戻り先がなければ一覧などに飛ばす（任意の安全策）
        return redirect()->route('item')->with('error', '商品が指定されていません。');
    }

    $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);

    return view('address', [
        'profile' => $profile,
        'item_id' => $itemId,   // ← フォーム hidden に埋め込む
    ]);
}


    /** 住所更新 → 購入画面へ戻る */
    public function update(Request $request)
    {
        // item_id を最初に拾っておく（エラー時の戻り先に使える）
        $itemId = (int) $request->input('item_id');

        $validated = $request->validate([
            'postal_code' => ['nullable','string','max:20'],
            'address1'    => ['required','string','max:255'], // ← 住所は必須にしておくと親切
            'address2'    => ['nullable','string','max:255'],
            'phone'       => ['nullable','string','max:50'],
            'item_id'     => ['bail','required','integer','exists:products,id'],
        ]);

        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);

        // プロフィールに関係あるキーだけ反映（item_id は除外）
        $profile->fill(Arr::only($validated, ['postal_code','address1','address2','phone']));
        $profile->save();

        return redirect()
            ->route('purchase', ['item_id' => $validated['item_id']])
            ->with('success', '住所情報を更新しました。');
    }
}
