<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

// 必要なら残してOK（一覧を将来使う場合）
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Profile;

class ProfileController extends Controller
{
    /** （任意）マイプロフィール表示
     *  今回は編集画面を出す想定なので、showで edit を返しています。
     *  出品/購入の取得は将来使うときに復活させてください。
     */
    public function show(Request $request)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        $user->load('profile');

        return view('edit', [
            'user'     => $user,
            'profile'  => $user->profile,
            'user_name'=> $user->name ?? 'ユーザー',
            // 'listedProducts'    => $listedProducts,
            // 'purchasedProducts' => $purchasedProducts,
        ]);
    }

    /** プロフィール編集（フォーム表示） */
    public function edit(Request $request)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        $user->load('profile');

        return view('edit', [
            'user'     => $user,
            'profile'  => $user->profile, // display_name / 住所 / アイコン 等
        ]);
    }

    /** プロフィール更新（フォーム送信先：PUT|PATCH /profile） */
    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        // ★ 新スキーマに合わせたバリデーション
        $validated = $request->validate([
            // プロフィール側の“ユーザー名”
            'display_name'      => ['required','string','max:255'],

            // 画像
            'icon'              => ['nullable','image','mimes:jpg,jpeg,png,webp,gif','max:4096'],

            // 住所系
            'postal_code'       => ['nullable','string','max:16'],
            'address_pref_city' => ['nullable','string','max:255'], // 都道府県市区町村
            'address_street'    => ['nullable','string','max:255'], // 番地
            'building_name'     => ['nullable','string','max:255'], // マンション名
        ]);

        // プロフィール（user_id は unique なので明示）
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        // ★ 旧フォーム名からの下位互換（もし name/address/building で飛んで来ても動かす）
        //   - users.name を display_name と同期したい場合は下の optional sync を使う
        $validated['display_name']   = $validated['display_name'] ?? $request->input('name');
        $validated['address_street'] = $validated['address_street'] ?? $request->input('address');
        $validated['building_name']  = $validated['building_name']  ?? $request->input('building');

        // ★ 新カラムへ反映
        $profile->display_name      = $validated['display_name'];
        $profile->postal_code       = $validated['postal_code']       ?? null;
        $profile->address_pref_city = $validated['address_pref_city'] ?? null;
        $profile->address_street    = $validated['address_street']    ?? null;
        $profile->building_name     = $validated['building_name']     ?? null;

        // 画像アップロード
        if ($request->hasFile('icon')) {
            if (!empty($profile->icon_image_path)) {
                Storage::disk('public')->delete($profile->icon_image_path);
            }
            $profile->icon_image_path = $request->file('icon')->store('profile_icons', 'public');
        }

        $profile->save();

        // （任意）users.name と表示名を同期したい場合は有効化
        // if (($user->name ?? null) !== $profile->display_name) {
        //     $user->name = $profile->display_name;
        //     $user->save();
        // }

        // ✅ 要件：更新後はマイページへ
        return redirect()->route('mypage')->with('status', 'プロフィールを更新しました。');
    }
}
