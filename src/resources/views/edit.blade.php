@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/edit.css') }}">
@endsection

@section('content')
@php
  $displayName = old('display_name', $profile->display_name ?? $user->name ?? 'ユーザー');
  $iconPath = ($profile?->icon_image_path)
      ? asset('storage/'.$profile->icon_image_path)
      : asset('img/sample.jpg');
@endphp

<main class="profile-edit__main container">
    <h1 class="profile-edit__title">プロフィール設定</h1>

    <form id="profileEditForm"
          class="profile-edit__form"
          action="{{ route('profile.update') }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        {{-- アイコン --}}
        <div class="profile-edit__icon-block">
            <figure class="profile-edit__icon">
                <img src="{{ $iconPath }}" alt="{{ $displayName.' のアイコン' }}">
            </figure>
            <label class="profile-edit__file-label">
                画像を選択する
                <input type="file" name="icon" class="sr-only" accept="image/*">
            </label>
            @error('icon')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        {{-- 表示名（profiles.display_name） --}}
        <label class="form__group">
            <span class="form__label">ユーザー名</span>
            <input type="text"
                   name="display_name"
                   value="{{ $displayName }}"
                   required
                   autocomplete="name"
                   aria-required="true">
            @error('display_name') <p class="error">{{ $message }}</p> @enderror
        </label>

        {{-- 住所系：必要なら編集 --}}
        <label class="form__group">
            <span class="form__label">郵便番号</span>
            <input type="text" name="postal_code" value="{{ old('postal_code', $profile->postal_code ?? '') }}">
            @error('postal_code') <p class="error">{{ $message }}</p> @enderror
        </label>

        <label class="form__group">
            <span class="form__label">住所1（都道府県市区町村）</span>
            <input type="text" name="address_pref_city" value="{{ old('address_pref_city', $profile->address_pref_city ?? '') }}">
            @error('address_pref_city') <p class="error">{{ $message }}</p> @enderror
        </label>

        <label class="form__group">
            <span class="form__label">住所2（番地）</span>
            <input type="text" name="address_street" value="{{ old('address_street', $profile->address_street ?? '') }}">
            @error('address_street') <p class="error">{{ $message }}</p> @enderror
        </label>

        <label class="form__group">
            <span class="form__label">マンション名</span>
            <input type="text" name="building_name" value="{{ old('building_name', $profile->building_name ?? '') }}">
            @error('building_name') <p class="error">{{ $message }}</p> @enderror
        </label>

        <button type="submit" class="profile-edit__submit">更新する</button>

        @if (session('status'))
            <p class="status">{{ session('status') }}</p>
        @endif
    </form>
</main>
@endsection
