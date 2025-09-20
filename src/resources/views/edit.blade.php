{{-- resources/views/edit.blade.php --}}
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/edit.css') }}">
@endsection

@section('content')
<main class="profile-edit__main container">
    <h1 class="profile-edit__title">プロフィール設定</h1>

    {{-- 送信先は /profile（PATCH）に固定 --}}
    <form id="profileEditForm"
          class="profile-edit__form"
          action="{{ url('/profile') }}"
          method="POST"
          enctype="multipart/form-data"
          onsubmit="this.action='{{ url('/profile') }}'">
        @csrf
        @method('PATCH')

        {{-- アイコン --}}
        <div class="profile-edit__icon-block">
            <figure class="profile-edit__icon">
                <img
                    src="{{ ($profile?->icon_image_path)
                            ? asset('storage/'.$profile->icon_image_path)
                            : asset('img/sample.jpg') }}"
                    alt="{{ ($profile->display_name ?? $user->name ?? 'ユーザー') . ' のアイコン' }}">
            </figure>
            <label class="profile-edit__file-label">
                画像を選択する
                <input type="file" name="icon" class="sr-only" accept="image/*">
            </label>
            @error('icon')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        {{-- プロフィール表示名（profiles.display_name） --}}
        <label class="form__group">
            <span class="form__label">ユーザー名</span>
            <input type="text"
                   name="display_name"
                   value="{{ old('display_name', $profile->display_name ?? $user->name ?? '') }}"
                   autocomplete="name"
                   required
                   aria-required="true">
            @error('display_name') <p class="error">{{ $message }}</p> @enderror
        </label>

        {{-- 郵便番号（profiles.postal_code） --}}
        <label class="form__group">
            <span class="form__label">郵便番号</span>
            <input type="text"
                   name="postal_code"
                   value="{{ old('postal_code', $profile->postal_code ?? '') }}">
            @error('postal_code') <p class="error">{{ $message }}</p> @enderror
        </label>

        {{-- 住所1：都道府県市区町村（profiles.address_pref_city） --}}
        <label class="form__group">
            <span class="form__label">住所1（都道府県市区町村）</span>
            <input type="text"
                   name="address_pref_city"
                   value="{{ old('address_pref_city', $profile->address_pref_city ?? '') }}">
            @error('address_pref_city') <p class="error">{{ $message }}</p> @enderror
        </label>

        {{-- 住所2：番地（profiles.address_street） --}}
        <label class="form__group">
            <span class="form__label">住所2（番地）</span>
            <input type="text"
                   name="address_street"
                   value="{{ old('address_street', $profile->address_street ?? '') }}">
            @error('address_street') <p class="error">{{ $message }}</p> @enderror
        </label>

        {{-- マンション名（profiles.building_name） --}}
        <label class="form__group">
            <span class="form__label">マンション名</span>
            <input type="text"
                   name="building_name"
                   value="{{ old('building_name', $profile->building_name ?? '') }}">
            @error('building_name') <p class="error">{{ $message }}</p> @enderror
        </label>

        <button type="submit" class="profile-edit__submit">更新する</button>

        @if (session('status'))
            <p class="status">{{ session('status') }}</p>
        @endif
    </form>
</main>
@endsection
