<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Profile;

class ProfileController extends Controller
{
    public function __construct()
    {
        // 認証必須（メール確認も必須にしたいなら 'verified' を追加）
        $this->middleware('auth');
    }

    /** プロフィール編集フォーム表示 */
    public function edit(Request $request)
    {
        $user = Auth::user(); // middleware で保証されるが念のため
        abort_if(!$user, 403);

        $user->load('profile');

        return view('edit', [
            'user'    => $user,
            'profile' => $user->profile,
        ]);
    }

    /** プロフィール更新（PATCH /profile） */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_if(!$user, 403);

        // 旧フォーム名（address/building_name）にも対応
        $validated = $request->validate([
            'display_name'  => ['required', 'string', 'max:255'],
            'icon'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'postal_code'   => ['nullable', 'string', 'max:16'],

            // 新フォーム推奨：address1/address2
            'address1'      => ['nullable', 'string', 'max:255'],
            'address2'      => ['nullable', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],

            // 互換（旧フォーム名）
            'address'       => ['nullable', 'string', 'max:255'],
            'building_name' => ['nullable', 'string', 'max:255'],
        ]);

        // プロフィールが無ければ作成
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $isFirstSetup = ($profile->wasRecentlyCreated ?? false) && $user->hasVerifiedEmail();

        // 入力マッピング：旧フォーム名を新項目へ吸収
        $address1 = $validated['address1'] ?? $validated['address'] ?? null;
        $address2 = $validated['address2'] ?? $validated['building_name'] ?? null;

        // ====== 保存 ======
        // users.name に display_name を反映（profiles に display_name カラムが無いため）
        $user->name = $validated['display_name'];
        $user->save();

        // profiles 側
        $profile->postal_code = $validated['postal_code'] ?? null;
        $profile->address1    = $address1;
        $profile->address2    = $address2;
        $profile->phone       = $validated['phone'] ?? $profile->phone;

        // アイコンのアップデート
        if ($request->hasFile('icon')) {
            if (!empty($profile->icon_image_path)) {
                Storage::disk('public')->delete($profile->icon_image_path);
            }
            $profile->icon_image_path = $request->file('icon')->store('profile_icons', 'public');
        }

        $profile->save();
        // ====== /保存 ======

        // 初回セット（メール認証済み）ならトップへ、それ以外はマイページへ
        $redirectTo = $isFirstSetup ? '/' : '/mypage';

        return redirect($redirectTo)->with('status', 'プロフィールを更新しました。');
    }
}
