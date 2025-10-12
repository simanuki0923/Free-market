<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * プロフィール編集画面
     * GET /mypage/profile  (name: mypage.profile)
     */
    public function edit(Request $request)
    {
        $user = $request->user();

        // プロフィールが無い場合でもフォームが空で崩れないように初期化
        $profile = $user->profile ?: new Profile([
            'postal_code' => '',
            'address1'    => '',
            'address2'    => '',
            'phone'       => '',
            'icon_image_path' => null,
        ]);

        return view('edit', compact('user', 'profile'));
    }

    /**
     * プロフィール更新
     * PATCH /profile  (name: profile.update)
     *
     * 画面の入力名とDBカラムの乖離を、この中で吸収します。
     * - address_pref_city → address1
     * - building_name     → address2
     * - nullは既存値を保持して上書きしない
     * - 画像は public/profile_icons/ に保存し、旧ファイルがあれば削除
     * - 初回作成かつメール認証済みならトップ('/')、それ以外は /mypage へ
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // バリデーション（実装に合わせて調整可）
        // 既存のFormRequestがある場合はそちらを型指定に差し替えてください。
        $validated = $request->validate([
            'display_name'      => ['required', 'string', 'max:50'],
            'postal_code'       => ['nullable', 'string', 'max:20'],

            // 住所は必須（画面側の入力名が address_pref_city でも通せるよう後段で吸収）
            'address'           => ['nullable', 'string'],
            'address1'          => ['nullable', 'string'],
            'address_pref_city' => ['nullable', 'string'],

            // 建物名は任意
            'address2'          => ['nullable', 'string'],
            'building_name'     => ['nullable', 'string'],

            'phone'             => ['nullable', 'string', 'max:30'],
            'icon'              => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        // いずれかの住所入力が無ければエラー（画面実装に寄せる）
        if (
            !$request->filled('address') &&
            !$request->filled('address1') &&
            !$request->filled('address_pref_city')
        ) {
            return back()
                ->withErrors(['address_pref_city' => '住所は必須です。'])
                ->withInput();
        }

        // 初回作成判定（保存前に見る）
        $isFirstProfile = !$user->profile()->exists();

        // ===== 入力名の吸収（旧/別名 → 統一名） =====
        // address1 は address / address_pref_city も吸収
        $address1 = $validated['address1']
            ?? $validated['address']
            ?? $validated['address_pref_city']
            ?? null;

        // address2 は building_name も吸収
        $address2 = $validated['address2']
            ?? $validated['building_name']
            ?? null;

        // ===== users 更新 =====
        $user->name = $validated['display_name'];
        $user->save();

        // ===== profiles 作成/更新（nullで既存値を潰さない） =====
        $profile = $user->profile ?: new Profile(['user_id' => $user->id]);

        $profile->postal_code = $validated['postal_code'] ?? $profile->postal_code;
        $profile->address1    = $address1 ?? $profile->address1;
        $profile->address2    = $address2 ?? $profile->address2;
        $profile->phone       = $validated['phone'] ?? $profile->phone;

        // 画像アップロード（旧ファイル削除 → 新規保存）
        if ($request->hasFile('icon')) {
            $oldPath = $profile->icon_image_path;

            $newPath = $request->file('icon')->store('profile_icons', 'public');
            $profile->icon_image_path = $newPath;

            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $profile->save();

        // ===== リダイレクト先 =====
        $redirectTo = ($isFirstProfile && method_exists($user, 'hasVerifiedEmail') && $user->hasVerifiedEmail())
            ? route('item')     // '/'
            : route('mypage');  // '/mypage'

        return redirect($redirectTo)->with('status', 'プロフィールを更新しました。');
    }
}
