@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/address.css') }}">
@endsection

@section('content')
<main class="edit-address__main container">
  <h1>住所の変更</h1>

  @if (session('success'))
    <p class="flash flash--success">{{ session('success') }}</p>
  @endif
  @if (session('error'))
    <p class="flash flash--error">{{ session('error') }}</p>
  @endif

  @if ($errors->any())
    <div class="flash flash--error" role="alert" aria-live="assertive">
      入力内容を確認してください。
    </div>
  @endif

  <form action="{{ route('purchase.address.update') }}" method="POST" novalidate>
    @csrf
    @method('PATCH')

    <input type="hidden" name="item_id" value="{{ old('item_id', $item_id) }}">

    <div class="form-row">
      <label for="postal_code">郵便番号 <span class="required" aria-label="必須"></span></label>
      <input
        type="text"
        id="postal_code"
        name="postal_code"
        required
        inputmode="numeric"
        pattern="\d{3}-\d{4}"
        maxlength="8"
        autocomplete="postal-code"
        value="{{ old('postal_code', $profile->postal_code ?? '') }}"
        aria-describedby="postal_code_help"
      >
      @error('postal_code')
        <p class="error-message">{{ $message }}</p>
      @enderror
    </div>

    <div class="form-row">
      <label for="address1">住所 <span class="required" aria-label="必須"></span></label>
      <input
        type="text"
        id="address1"
        name="address1"
        required
        autocomplete="street-address"
        value="{{ old('address1', $profile->address1 ?? '') }}"
      >
      @error('address1')
        <p class="error-message">{{ $message }}</p>
      @enderror
    </div>

    <div class="form-row">
      <label for="address2">建物名</label>
      <input
        type="text"
        id="address2"
        name="address2"
        autocomplete="address-line2"
        value="{{ old('address2', $profile->address2 ?? '') }}"
      >
      @error('address2')
        <p class="error-message">{{ $message }}</p>
      @enderror
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">更新する</button>
    </div>
  </form>
</main>
@endsection
