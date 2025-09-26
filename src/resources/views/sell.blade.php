@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/sell.css') }}">
@endsection

@section('content')
<main class="sell-form__main container">
  <h1 class="sell-title">商品の出品</h1>

  <form class="sell-form" action="{{ route('sell.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <fieldset class="sell-fieldset">
       <legend class="sell-legend sell-legend--no-underline">商品画像</legend>
      <div class="sell-image-drop">
        <input id="image" name="image" type="file" accept="image/*">
        <label for="image" class="sell-image-button">画像を選択する</label>
      </div>
      <figure class="sell-preview" style="display:none;">
        <img id="imagePreview" alt="画像プレビュー">
      </figure>
      @error('image') <p class="sell-error">{{ $message }}</p> @enderror
    </fieldset>

    <fieldset class="sell-fieldset">
      <legend class="sell-legend">商品の詳細</legend>

      {{-- カテゴリー（複数選択・チップ風） --}}
<label class="sell-label">カテゴリー <small>（複数選択可）</small></label>
@php
  $oldSelected = collect(old('categories', []))->map(fn($v)=>(string)$v)->all();
@endphp

<div class="chip-group" role="group" aria-label="カテゴリーを選択">
  @foreach(($categoriesList ?? collect()) as $cat)
    <label class="chip">
      <input
        type="checkbox"
        name="categories[]"
        value="{{ $cat['val'] }}"
        {{ in_array((string)$cat['val'], $oldSelected, true) ? 'checked' : '' }}
      >
      <span>{{ $cat['name'] }}</span>
    </label>
  @endforeach
</div>
@error('categories') <p class="sell-error">{{ $message }}</p> @enderror


      <label class="sell-label" for="condition">商品の状態</label>
      @php
        $conditionList = ['新品・未使用','未使用に近い','目立った傷や汚れなし','やや傷や汚れあり','傷や汚れあり','全体的に状態が悪い'];
      @endphp
      <select id="condition" name="condition" class="sell-input">
        <option value="" disabled {{ old('condition') ? '' : 'selected' }}></option>
        @foreach($conditionList as $cond)
          <option value="{{ $cond }}" {{ old('condition') === $cond ? 'selected' : '' }}>{{ $cond }}</option>
        @endforeach
      </select>
      @error('condition') <p class="sell-error">{{ $message }}</p> @enderror
    </fieldset>

    <fieldset class="sell-fieldset">
      <legend class="sell-legend">商品名と説明</legend>

      <label class="sell-label" for="name">商品名</label>
      <input id="name" name="name" type="text" class="sell-input" value="{{ old('name') }}" maxlength="100">
      @error('name') <p class="sell-error">{{ $message }}</p> @enderror

      <label class="sell-label" for="brand">ブランド名</label>
      <input id="brand" name="brand" type="text" class="sell-input" value="{{ old('brand') }}" maxlength="100">
      @error('brand') <p class="sell-error">{{ $message }}</p> @enderror

      <label class="sell-label" for="description">商品の説明</label>
      <textarea id="description" name="description" class="sell-textarea" rows="8" maxlength="2000">{{ old('description') }}</textarea>
      @error('description') <p class="sell-error">{{ $message }}</p> @enderror
    </fieldset>

    <fieldset class="sell-fieldset">
      <label class="sell-label" for="price">販売価格</label>
      <div class="sell-price">
        <span class="yen">¥</span>
        <input id="price" name="price" type="number" class="sell-input sell-input--price"
               inputmode="numeric" pattern="[0-9]*" min="1" step="1" value="{{ old('price') }}" placeholder="0">
      </div>
      @error('price') <p class="sell-error">{{ $message }}</p> @enderror
    </fieldset>

    <form action="{{ route('sell.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <button type="submit" class="sell-submit">出品する</button>
</form>

  </form>
</main>

<script>
(function () {
  const input = document.getElementById('image');
  const box   = document.querySelector('.sell-preview');
  const img   = document.getElementById('imagePreview');
  if (input && img && box) {
    input.addEventListener('change', (e) => {
      const [file] = e.target.files || [];
      if (file) {
        const url = URL.createObjectURL(file);
        img.src = url; box.style.display = 'block';
        img.onload = () => URL.revokeObjectURL(url);
      } else { box.style.display = 'none'; img.removeAttribute('src'); }
    });
  }
  const price = document.getElementById('price');
  if (price) price.addEventListener('keydown', (e) => {
    if (['e','E','+','-','.'].includes(e.key)) e.preventDefault();
  });
})();
</script>
@endsection
