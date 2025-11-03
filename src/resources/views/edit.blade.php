@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/edit.css') }}">
@endsection

@section('content')
@php
  $displayName = old('display_name', $profile->display_name ?? $user->name ?? 'ユーザー');
  $iconPath = ($profile?->icon_image_path)
      ? asset('storage/'.$profile->icon_image_path)
      : '';
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

    <div class="profile-edit__icon-block">
      <figure class="profile-edit__icon">
        <img id="iconPreview" src="{{ $iconPath }}" alt="">
      </figure>

      <label class="profile-edit__file-label">
        画像を選択する
        <input id="iconInput"
               type="file"
               name="icon"
               class="sr-only"
               accept=".jpg,.jpeg,.png,image/jpeg,image/png">
      </label>
      @error('icon')
        <p class="error">{{ $message }}</p>
      @enderror
    </div>

    <label class="form__group">
      <span class="form__label">ユーザー名</span>
      <input type="text"
             name="display_name"
             value="{{ old('display_name', $displayName) }}"
             maxlength="20"
             required
             autocomplete="name"
             aria-required="true">
      @error('display_name') <p class="error">{{ $message }}</p> @enderror
    </label>

    <label class="form__group">
      <span class="form__label">郵便番号</span>
      <input type="text"
             name="postal_code"
             value="{{ old('postal_code', $profile->postal_code ?? '') }}"
             placeholder="123-4567"
             inputmode="numeric"
             maxlength="8"
             required>
      @error('postal_code') <p class="error">{{ $message }}</p> @enderror
    </label>

    <label class="form__group">
      <span class="form__label">住所</span>
      <input type="text"
             name="address_pref_city"
             value="{{ old('address_pref_city', $profile->address_pref_city ?? $profile->address1 ?? '') }}"
             required>
      @error('address_pref_city') <p class="error">{{ $message }}</p> @enderror
    </label>

    <label class="form__group">
      <span class="form__label">マンション名</span>
      <input type="text"
             name="building_name"
             value="{{ old('building_name', $profile->building_name ?? $profile->address2 ?? '') }}">
      @error('building_name') <p class="error">{{ $message }}</p> @enderror
    </label>

    <button type="submit" class="profile-edit__submit">更新する</button>

    @if (session('status'))
      <p class="status">{{ session('status') }}</p>
    @endif
  </form>
</main>

<script>
  (function () {
    const input  = document.getElementById('iconInput');
    const img    = document.getElementById('iconPreview');
    const form   = document.getElementById('profileEditForm');
    const maxMB  = 5;
    if (!input || !img) return;

    let tempURL = null;
    const revokeTempURL = () => {
      if (tempURL) {
        URL.revokeObjectURL(tempURL);
        tempURL = null;
      }
    };

    input.addEventListener('change', (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;

      const okTypes = ['image/jpeg', 'image/png'];
      if (!okTypes.includes(file.type)) {
        alert('JPEG または PNG を選択してください。');
        input.value = '';
        return;
      }
      if (file.size > maxMB * 1024 * 1024) {
        alert(`ファイルサイズは ${maxMB}MB 以下にしてください。`);
        input.value = '';
        return;
      }

      revokeTempURL();
      tempURL = URL.createObjectURL(file);
      img.src = tempURL;

      img.onload = () => revokeTempURL();
    });

    if (form) {
      const initialSrc = img.src;
      form.addEventListener('reset', () => {
        input.value = '';
        revokeTempURL();
        img.src = initialSrc;
      });
    }

    window.addEventListener('beforeunload', revokeTempURL);
  })();
</script>
@endsection
