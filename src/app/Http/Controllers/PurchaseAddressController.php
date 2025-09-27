<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Profile;

class PurchaseAddressController extends Controller
{
    /**
     * 住所編集フォーム表示
     * GET /purchase/address/edit?item_id=123
     */
    public function edit(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);
        $itemId  = (int) $request->query('item_id', 0);

        return view('address', compact('profile', 'itemId'));
    }

    /**
     * 住所更新（PATCH）→ 購入画面へ遷移
     * PATCH /purchase/address
     */
    public function update(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'postal_code'   => ['nullable','string','max:20'],
            'address'       => ['required','string','max:255'],
            'building_name' => ['nullable','string','max:255'],
            'item_id'       => ['nullable','integer'],
        ]);

        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);

        // 指定の3項目のみ更新（telは除外）
        $profile->fill([
            'postal_code'   => $validated['postal_code']   ?? $profile->postal_code,
            'address'       => $validated['address']       ?? $profile->address,
            'building_name' => $validated['building_name'] ?? $profile->building_name,
        ])->save();

        $itemId = $validated['item_id'] ?? null;

        if ($itemId) {
            return redirect()
                ->route('purchase.show', ['item_id' => (int) $itemId])
                ->with('success', '住所を更新しました。');
        }

        return redirect()->route('item')->with('success', '住所を更新しました。');
    }
}
