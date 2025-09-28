@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/address.css') }}">
@endsection

@section('content')
<main class="edit-address__main container">
  <h1>住所の変更</h1>

  {{-- フラッシュメッセージ --}}
  @if (session('success'))
    <p class="flash flash--success">{{ session('success') }}</p>
  @endif
  @if (session('error'))
    <p class="flash flash--error">{{ session('error') }}</p>
  @endif

  {{-- バリデーションエラー（サマリ） --}}
  @if ($errors->any())
    <div class="flash flash--error" role="alert" aria-live="assertive">
      入力内容を確認してください。
    </div>
  @endif

  {{-- 更新フォーム：PATCH /purchase/address --}}
  <form action="{{ route('purchase.address.update') }}" method="POST" novalidate>
    @csrf
    @method('PATCH')

    {{-- 確実に item_id を渡す --}}
    <input type="hidden" name="item_id" value="{{ $item_id }}">

    {{-- 郵便番号（profiles.postcode） --}}
    <div class="form-row">
      <label for="postcode">郵便番号</label>
      <input
        type="text"
        id="postcode"
        name="postcode"
        inputmode="numeric"
        pattern="\d{3}-?\d{4}"
        autocomplete="postal-code"
        value="{{ old('postcode', $profile->postcode ?? '') }}"
        aria-describedby="postcode_help"
      >
      @error('postcode')
        <p class="error-message">{{ $message }}</p>
      @enderror
    </div>

    {{-- 住所（必須）profiles.address --}}
    <div class="form-row">
      <label for="address">住所 <span class="required" aria-label="必須"></span></label>
      <input
        type="text"
        id="address"
        name="address"
        required
        autocomplete="street-address"
        value="{{ old('address', $profile->address ?? '') }}"
      >
      @error('address')
        <p class="error-message">{{ $message }}</p>
      @enderror
    </div>

    {{-- 建物名（任意）profiles.building_name --}}
    <div class="form-row">
      <label for="building_name">建物名</label>
      <input
        type="text"
        id="building_name"
        name="building_name"
        autocomplete="address-line2"
        value="{{ old('building_name', $profile->building_name ?? '') }}"
      >
      @error('building_name')
        <p class="error-message">{{ $message }}</p>
      @enderror
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">更新する</button>
    </div>
  </form>
</main>
@endsection
