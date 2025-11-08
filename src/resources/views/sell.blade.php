@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/sell.css') }}">
@endsection

@section('content')
<main class="sell-form__main container">
  <h1 class="sell-title">商品の出品</h1>

  @if (session('status'))
    <div class="sell-form__status">{{ session('status') }}</div>
  @endif

  <form class="sell-form" action="{{ route('sell.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <fieldset class="sell-fieldset">
      <legend class="sell-legend sell-legend--no-underline">商品画像</legend>

      <div class="sell-image-drop">
        <input
          id="image"
          name="image"
          type="file"
          accept=".jpg,.jpeg,.png,image/jpeg,image/png"
          @if(!old('image')) required @endif
        >
        <label for="image" class="sell-image-button">画像を選択する</label>
      </div>

      <figure class="sell-preview" style="display:none;">
        <img id="imagePreview" alt="画像プレビュー">
      </figure>

      @error('image')
        <p class="sell-error">{{ $message }}</p>
      @enderror
    </fieldset>

    <fieldset class="sell-fieldset">
      <legend class="sell-legend">商品の詳細</legend>

      <label class="sell-label">カテゴリー</label>
      @php
        $oldSelectedIds = collect(old('categories', []))
          ->map(fn($v) => (string)$v)
          ->all();
      @endphp

      <div class="chip-group" role="group" aria-label="カテゴリーを選択">
        @foreach($categories as $category)
          @php
            if (in_array($category->id, [1, 2], true)) { continue; }
            $val  = (string)$category->id;
            $name = (string)$category->name;
          @endphp
          <label class="chip">
            <input
              type="checkbox"
              name="categories[]"
              value="{{ $val }}"
              {{ in_array($val, $oldSelectedIds, true) ? 'checked' : '' }}
            >
            <span>{{ $name }}</span>
          </label>
        @endforeach
      </div>

      @error('categories')   <p class="sell-error">{{ $message }}</p> @enderror
      @error('categories.*') <p class="sell-error">{{ $message }}</p> @enderror

      <label class="sell-label" for="condition-native">商品の状態</label>
      @php
        $condList = ['良好','目立った傷や汚れなし','やや傷や汚れあり','状態が悪い'];
        $oldCond  = old('condition');
      @endphp

      <div class="selectbox" data-selectbox>
        <select id="condition-native" name="condition" class="sr-only-select" required>
          <option value="" disabled {{ $oldCond ? '' : 'selected' }}></option>
          @foreach($condList as $cond)
            <option value="{{ $cond }}" {{ $oldCond === $cond ? 'selected' : '' }}>{{ $cond }}</option>
          @endforeach
        </select>

        <button type="button"
                class="selectbox__trigger sell-input"
                aria-haspopup="listbox"
                aria-expanded="false"
                aria-controls="condition-listbox">
          <span class="selectbox__label">{{ $oldCond ?: '選択してください' }}</span>
          <span class="selectbox__caret" aria-hidden="true"></span>
        </button>

        <ul id="condition-listbox" class="selectbox__list" role="listbox" tabindex="-1">
          @foreach($condList as $cond)
            <li role="option"
                class="selectbox__option {{ $oldCond === $cond ? 'is-selected' : '' }}"
                data-value="{{ $cond }}"
                aria-selected="{{ $oldCond === $cond ? 'true' : 'false' }}"
                tabindex="-1">
              <span class="selectbox__check" aria-hidden="true"></span>
              <span class="selectbox__text">{{ $cond }}</span>
            </li>
          @endforeach
        </ul>
      </div>

      @error('condition')
        <p class="sell-error">{{ $message }}</p>
      @enderror
    </fieldset>

    <fieldset class="sell-fieldset">
      <legend class="sell-legend">商品名と説明</legend>

      <label class="sell-label" for="name">商品名</label>
      <input id="name" name="name" type="text" class="sell-input"
             value="{{ old('name') }}" maxlength="100" required>
      @error('name') <p class="sell-error">{{ $message }}</p> @enderror

      <label class="sell-label" for="brand">ブランド名</label>
      <input id="brand" name="brand" type="text" class="sell-input"
             value="{{ old('brand') }}" maxlength="100">
      @error('brand') <p class="sell-error">{{ $message }}</p> @enderror

      <label class="sell-label" for="description">商品の説明</label>
      <textarea id="description" name="description" class="sell-textarea"
                rows="8" maxlength="255" required>{{ old('description') }}</textarea>
      @error('description') <p class="sell-error">{{ $message }}</p> @enderror
    </fieldset>

    <fieldset class="sell-fieldset">
      <label class="sell-label" for="price">販売価格</label>
      <div class="sell-price">
        <span class="yen" aria-hidden="true">¥</span>
        <input id="price" name="price" type="number"
                class="sell-input sell-input--price"
                inputmode="numeric" pattern="[0-9]*"
                min="0" step="1"
                value="{{ old('price') }}" placeholder="0" required>
      </div>
      @error('price') <p class="sell-error">{{ $message }}</p> @enderror
    </fieldset>

    <button type="submit" class="sell-submit">出品する</button>
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
        img.src = url;
        box.style.display = 'block';
        img.onload = () => URL.revokeObjectURL(url);
      } else {
        box.style.display = 'none';
        img.removeAttribute('src');
      }
    });
  }

  const price = document.getElementById('price');
  if (price) {
    price.addEventListener('keydown', (e) => {
      if (['e','E','+','-','.'].includes(e.key)) e.preventDefault();
    });
    price.addEventListener('wheel', (e) => e.preventDefault(), { passive: false });
    price.addEventListener('input', () => {
      const onlyDigits = price.value.replace(/[^\d]/g, '');
      price.value = onlyDigits.replace(/^0+(?=\d)/, '');
    });
  }

  const root = document.querySelector('[data-selectbox]');
  if (!root) return;

  const native  = document.getElementById('condition-native');
  const trigger = root.querySelector('.selectbox__trigger');
  const labelEl = root.querySelector('.selectbox__label');
  const list    = root.querySelector('.selectbox__list');
  const opts    = Array.from(root.querySelectorAll('.selectbox__option'));

  const open = () => {
    root.classList.add('is-open');
    trigger.setAttribute('aria-expanded','true');
    list.focus({ preventScroll:true });
  };

  const close = (returnFocus = false) => {
    root.classList.remove('is-open');
    trigger.setAttribute('aria-expanded','false');
    if (returnFocus) trigger.focus({ preventScroll:true });
  };

  const setByValue = (val) => {
    opts.forEach(o => {
      const sel = (o.dataset.value || '') === val;
      o.classList.toggle('is-selected', sel);
      o.setAttribute('aria-selected', String(sel));
    });
    labelEl.textContent = val || '選択してください';
    if (native) native.value = val || '';
  };

  setByValue(native?.value || '');

  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    root.classList.contains('is-open') ? close(true) : open();
  });

  opts.forEach(o => {
    o.addEventListener('click', (e) => {
      e.stopPropagation();
      setByValue(o.dataset.value || '');
      close(true);
    });
  });

  document.addEventListener('click', (e) => {
    if (!root.contains(e.target)) close(false);
  });

  const focusIndex = () => opts.findIndex(o => o.classList.contains('is-selected'));
  list.addEventListener('keydown', (e) => {
    let idx = focusIndex();
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      idx = Math.min(opts.length - 1, (idx < 0 ? 0 : idx + 1));
      setByValue(opts[idx].dataset.value || '');
      opts[idx].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      idx = Math.max(0, (idx < 0 ? 0 : idx - 1));
      setByValue(opts[idx].dataset.value || '');
      opts[idx].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Home') {
      e.preventDefault();
      setByValue(opts[0].dataset.value || ''); opts[0].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'End') {
      e.preventDefault();
      const last = opts.at(-1); setByValue(last.dataset.value || '');
      last.scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault(); close(true);
    } else if (e.key === 'Escape') {
      e.preventDefault(); close(true);
    }
  });

  document.querySelectorAll('label[for="condition-native"]').forEach(l =>
    l.addEventListener('click', (e) => { e.preventDefault(); trigger.click(); })
  );
})();

document.getElementById('price')?.addEventListener('wheel', (e) => e.preventDefault(), { passive: false });

</script>
@endsection
