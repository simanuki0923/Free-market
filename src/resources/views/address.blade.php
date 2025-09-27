{{-- resources/views/address.blade.php --}}
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>住所の変更</title>
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
  <link rel="stylesheet" href="{{ asset('css/address.css') }}">
</head>
<body>
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
    <form
      action="{{ route('purchase.address.update', ['item_id' => $itemId]) }}"
      method="POST"
      class="address-form"
      novalidate
    >
      @csrf
      @method('PATCH')

      {{-- 確実に item_id を渡す（URL + hidden の二重化） --}}
      <input type="hidden" name="item_id" value="{{ $itemId }}">

      {{-- 郵便番号 --}}
      <div class="form-row">
        <label for="postal_code">郵便番号</label>
        <input
          type="text"
          id="postal_code"
          name="postal_code"
          inputmode="numeric"
          pattern="\d{3}-?\d{4}"
          value="{{ old('postal_code', $profile->postal_code ?? '') }}"
          aria-describedby="postal_code_help"
        >
        <small id="postal_code_help" class="form-help"></small>
        @error('postal_code')
          <p class="error-message">{{ $message }}</p>
        @enderror
      </div>

      {{-- 住所（必須） --}}
      <div class="form-row">
        <label for="address">住所 <span class="required"></span></label>
        <input
          type="text"
          id="address"
          name="address"
          required
          autocomplete="address"
          value="{{ old('address', $profile->address ?? '') }}"
        >
        @error('address')
          <p class="error-message">{{ $message }}</p>
        @enderror
      </div>

     {{-- 建物名（任意） --}}
<div class="form-row">
  <label for="building_name">建物名</label>
  <input
    type="text"
    id="building_name"
    name="building_name"
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
</body>
</html>

