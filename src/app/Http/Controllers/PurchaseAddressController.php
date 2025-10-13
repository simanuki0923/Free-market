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

    public function edit(Request $request)
    {
        $itemId = (int) ($request->query('item_id') ?? $request->route('item_id'));

        if ($itemId <= 0) {
            return redirect()->route('item')->with('error', '商品が指定されていません。');
        }

        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);

        return view('address', [
            'profile' => $profile,
            'item_id' => $itemId,
        ]);
    }

    public function update(AddressRequest $request)
    {
        $validated = $request->validated();
        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);
        $profile->fill(Arr::only($validated, ['postal_code','address1','address2','phone']));
        $profile->save();

        return redirect()
            ->route('purchase', ['item_id' => $validated['item_id']])
            ->with('success', '住所情報を更新しました。');
    }
}
