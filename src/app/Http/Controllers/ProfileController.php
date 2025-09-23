<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Profile;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /** 編集フォーム */
    public function edit(Request $request)
    {
        $user = Auth::user();
        abort_if(!$user, 403);

        $user->load('profile');

        return view('edit', [
            'user'    => $user,
            'profile' => $user->profile, // display_name / 住所 / アイコン
        ]);
    }

    /** 更新処理（PATCH /profile） */
    public function update(Request $request)
    {
        $user = Auth::user();
        abort_if(!$user, 403);

        $validated = $request->validate([
            'display_name'       => ['required','string','max:255'],
            'icon'               => ['nullable','image','mimes:jpg,jpeg,png,webp,gif','max:4096'],
            'postal_code'        => ['nullable','string','max:16'],
            'address_pref_city'  => ['nullable','string','max:255'],
            'address_street'     => ['nullable','string','max:255'],
            'building_name'      => ['nullable','string','max:255'],
        ]);

        // 既存 or 新規作成
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        // 「保存前」の値を使って初回セットか判定
        // - 新規作成直後（wasRecentlyCreated）
        // - もしくは display_name が未設定（空/NULL）
        $wasRecentlyCreated = $profile->wasRecentlyCreated ?? false;
        $wasDisplayNameEmpty = blank($profile->getOriginal('display_name'));
        $isFirstSetup = ($wasRecentlyCreated || $wasDisplayNameEmpty) && $user->hasVerifiedEmail();

        // 値を更新
        $profile->display_name       = $validated['display_name'];
        $profile->postal_code        = $validated['postal_code']        ?? null;
        $profile->address_pref_city  = $validated['address_pref_city']  ?? null;
        $profile->address_street     = $validated['address_street']     ?? null;
        $profile->building_name      = $validated['building_name']      ?? null;

        if ($request->hasFile('icon')) {
            if (!empty($profile->icon_image_path)) {
                Storage::disk('public')->delete($profile->icon_image_path);
            }
            $profile->icon_image_path = $request->file('icon')->store('profile_icons', 'public');
        }

        $profile->save();

        // ※ users.name と同期したい場合のみ有効化
        // $user->name = $profile->display_name;
        // $user->save();

        // 初回セット（メール認証後）→ '/'
        // 通常の更新 → '/mypege'
        $redirectTo = $isFirstSetup ? '/' : '/mypege';

        return redirect($redirectTo)->with('status', 'プロフィールを更新しました。');
    }
}
