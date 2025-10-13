<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        $profile = $user->profile ?: new Profile([
            'postal_code' => '',
            'address1'    => '',
            'address2'    => '',
            'phone'       => '',
            'icon_image_path' => null,
        ]);

        return view('edit', compact('user', 'profile'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'display_name'      => ['required', 'string', 'max:50'],
            'postal_code'       => ['nullable', 'string', 'max:20'],
            'address'           => ['nullable', 'string'],
            'address1'          => ['nullable', 'string'],
            'address_pref_city' => ['nullable', 'string'],
            'address2'          => ['nullable', 'string'],
            'building_name'     => ['nullable', 'string'],
            'phone'             => ['nullable', 'string', 'max:30'],
            'icon'              => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        if (
            !$request->filled('address') &&
            !$request->filled('address1') &&
            !$request->filled('address_pref_city')
        ) {
            return back()
                ->withErrors(['address_pref_city' => '住所は必須です。'])
                ->withInput();
        }

        $isFirstProfile = !$user->profile()->exists();

        $address1 = $validated['address1']
            ?? $validated['address']
            ?? $validated['address_pref_city']
            ?? null;

        $address2 = $validated['address2']
            ?? $validated['building_name']
            ?? null;

        $user->name = $validated['display_name'];
        $user->save();

        $profile = $user->profile ?: new Profile(['user_id' => $user->id]);

        $profile->postal_code = $validated['postal_code'] ?? $profile->postal_code;
        $profile->address1    = $address1 ?? $profile->address1;
        $profile->address2    = $address2 ?? $profile->address2;
        $profile->phone       = $validated['phone'] ?? $profile->phone;

        if ($request->hasFile('icon')) {
            $oldPath = $profile->icon_image_path;

            $newPath = $request->file('icon')->store('profile_icons', 'public');
            $profile->icon_image_path = $newPath;

            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $profile->save();

        $redirectTo = ($isFirstProfile && method_exists($user, 'hasVerifiedEmail') && $user->hasVerifiedEmail())
            ? route('item')
            : route('mypage');
        return redirect($redirectTo)->with('status', 'プロフィールを更新しました。');
    }
}
