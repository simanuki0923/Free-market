<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseAddressController extends Controller
{
    /**
     * 住所編集フォーム表示
     * GET /purchase/address/edit
     */
    public function edit(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $profile = Profile::firstOrNew(['user_id' => $user->id]);

        // 直前に見ていた商品IDをセッションに保存しておけば、更新後に戻せる
        // （PurchaseController@show で session(['last_purchase_item_id' => $product->id]) を入れておくのがオススメ）
        $lastItemId = session('last_purchase_item_id');

        return view('purchase_address_edit', [
            'profile'   => $profile,
            'lastItemId'=> $lastItemId,
        ]);
    }

    /**
     * 住所更新
     * PATCH /purchase/address
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:255'],
            'zip_code'     => ['nullable', 'string', 'max:16'],
            'prefecture'   => ['nullable', 'string', 'max:64'],
            'city'         => ['nullable', 'string', 'max:128'],
            'address_line1'=> ['nullable', 'string', 'max:255'],
            'address_line2'=> ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:32'],
        ]);

        $profile = Profile::firstOrNew(['user_id' => $user->id]);
        $profile->fill($data);
        $profile->user_id = $user->id;
        $profile->save();

        // 直前に開いていた購入ページへ戻す（無ければトップ）
        $itemId = session('last_purchase_item_id');
        if ($itemId) {
            return redirect()->route('purchase', ['item_id' => $itemId])
                ->with('status', '配送先を更新しました。');
        }

        return redirect()->route('item')->with('status', '配送先を更新しました。');
    }
}
