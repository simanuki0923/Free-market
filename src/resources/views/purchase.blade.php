@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('content')
@php
  $path = $product->image_path ?? optional($product->sell)->image_path;
  $src = $path
      ? (\Illuminate\Support\Str::startsWith($path, ['http://','https://'])
          ? $path
          : asset('storage/' . ltrim($path, '/')))
      : asset('img/no-image.png');

  $user        = auth()->user();
  $recipient   = $user?->name ?? 'お名前未設定';
  $postal      = optional($profile)->postal_code ?? '';
  $addr1       = optional($profile)->address1 ?? '';
  $addr2       = optional($profile)->address2 ?? '';
  $phone       = optional($profile)->phone ?? '';
  $addressDisp = trim($addr1 . ' ' . $addr2);

  // ★ 反映の要：old() → request() → DB/既存順で初期値を決定
  $initialPayment = old('payment_method', request('payment_method', optional($payment)->payment_method));
@endphp

<main class="purchase__main container">
  @if (session('status'))
    <div class="flash success" role="status">{{ session('status') }}</div>
  @endif
  @if (session('error'))
    <div class="flash error" role="alert">{{ session('error') }}</div>
  @endif

  <div class="purchase__grid">
    <section class="purchase__left">
      <article class="purchase-item card">
        <div class="purchase-item__body">
          <figure class="purchase-item__thumb">
            <img src="{{ $src }}" alt="{{ $product->name ?? '商品画像' }}">
          </figure>
          <div class="purchase-item__info">
            <h2 class="purchase-item__name">{{ $product->name }}</h2>
            <p class="purchase-item__price">¥ {{ number_format((int)$product->price) }}</p>
          </div>
        </div>
      </article>

      <article class="card">
          <header class="card__header">
            <h2 class="card__title">支払い方法</h2>
          </header>

        <div class="card__section">
            <form id="payment-form" action="{{ route('payment.create') }}" method="GET" class="purchase-form">
              <input type="hidden" name="item_id" value="{{ $product->id }}">

              @php
                $pmList  = ['convenience_store' => 'コンビニ支払い', 'credit_card' => 'カード支払い'];
                $pmValue = $initialPayment ?: '';                // 初期は未選択。初期✓を付けたいなら 'convenience_store'
                $pmLabel = $pmList[$pmValue] ?? '選択してください';
              @endphp

              <div class="selectbox" data-selectbox data-name="payment_method">
                <select id="payment-native" name="payment_method" class="sr-only-select" required>
                  <option value="" disabled {{ $pmValue ? '' : 'selected' }}></option>
                  @foreach($pmList as $val => $text)
                  <option value="{{ $val }}" {{ $pmValue === $val ? 'selected' : '' }}>{{ $text }}</option>
                  @endforeach
                </select>

                <button type="button"
                  class="selectbox__trigger sell-input"
                  aria-haspopup="listbox"
                  aria-expanded="false"
                  aria-controls="payment-listbox">
                  <span class="selectbox__label">{{ $pmLabel }}</span>
                  <span class="selectbox__caret" aria-hidden="true"></span>
                </button>

              <ul id="payment-listbox" class="selectbox__list" role="listbox" tabindex="-1">
                @foreach($pmList as $val => $text)
                @php $selected = ($pmValue === $val); @endphp
                <li role="option"
                  class="selectbox__option {{ $selected ? 'is-selected' : '' }}"
                  data-value="{{ $val }}"
                  aria-selected="{{ $selected ? 'true' : 'false' }}"
                  tabindex="-1">
                  <span class="selectbox__check" aria-hidden="true"></span>
                  <span class="selectbox__text">{{ $text }}</span>
                </li>
                @endforeach
              </ul>
            </div>
          </form>
        </div>
      </article>

      <article class="card">
        <header class="card__header card__header--with-action">
          <h2 class="card__title">配送先</h2>
          <a class="btn" href="{{ route('purchase.address.edit', ['item_id' => $product->id]) }}">変更する</a>
        </header>

        <div class="card__section card__section--with-line">
          <div class="address">
            <div class="address__row"><span class="muted"></span> {{ $postal ?: '未設定' }}</div>
            <div class="address__row"><span class="muted"></span> {{ $addressDisp ?: '未設定' }}</div>
          </div>
        </div>
      </article>
    </section>

    <aside class="purchase__right">
      <div class="aside-box">
        <article class="card">
          <div class="card__section summary__row">
            <span>商品代金</span>
            <strong>¥ {{ number_format((int)$product->price) }}</strong>
          </div>

          <div class="card__section summary__row">
            <span>支払い方法</span>
            <span id="summary-payment" aria-live="polite">
              @php
                $pmText = $initialPayment === 'credit_card' ? 'カード支払い'
                         : ($initialPayment === 'convenience_store' ? 'コンビニ支払い' : '未選択');
              @endphp
              {{ $pmText }}
            </span>
          </div>
        </article>
      </div>

      <button
        type="submit"
        form="payment-form"
        class="purchase-button--primary"
        {{ $product->is_sold ? 'disabled' : '' }}
      >
        購入する
      </button>

      @if ($product->is_sold)
        <p class="form-help">この商品は既にSOLDです。</p>
      @endif
    </aside>
  </div>
</main>

<script>
(function () {
  function pmLabel(v){
    if (v === 'credit_card') return 'カード支払い';
    if (v === 'convenience_store') return 'コンビニ支払い';
    return '選択してください';
  }

  var summary = document.getElementById('summary-payment');

  var root = document.querySelector('[data-selectbox][data-name="payment_method"]');
  if (!root) return;

  var native  = document.getElementById('payment-native');
  var trigger = root.querySelector('.selectbox__trigger');
  var labelEl = root.querySelector('.selectbox__label');
  var list    = root.querySelector('.selectbox__list');
  var opts    = Array.from(root.querySelectorAll('.selectbox__option'));

  function setByValue(val) {
    opts.forEach(o => {
      var sel = (o.dataset.value || '') === val;
      o.classList.toggle('is-selected', sel);
      o.setAttribute('aria-selected', String(sel));
    });
    labelEl.textContent = pmLabel(val) || '選択してください';
    if (native) native.value = val || '';
    if (summary) summary.textContent = pmLabel(val || '');
  }

  function openList() {
    root.classList.add('is-open');
    trigger.setAttribute('aria-expanded','true');
    opts.forEach(el => el.classList.remove('is-active'));
    var current = opts.find(el => el.classList.contains('is-selected'));
    if (current) current.classList.add('is-active');
    list.focus({ preventScroll: true });
  }
  function closeList(returnFocus=false) {
    root.classList.remove('is-open');
    trigger.setAttribute('aria-expanded','false');
    if (returnFocus) trigger.focus({ preventScroll:true });
  }

  setByValue(native?.value || '');

  trigger.addEventListener('click', function (e) {
    e.stopPropagation();
    if (root.classList.contains('is-open')) closeList(true); else openList();
  });

  opts.forEach(function (o) {
    o.addEventListener('click', function (e) {
      e.stopPropagation();
      setByValue(o.dataset.value || '');
      closeList(true);
    });
    o.addEventListener('mouseenter', function () {
      opts.forEach(el => el.classList.remove('is-active'));
      o.classList.add('is-active');
    });
  });

  document.addEventListener('click', function (e) {
    if (!root.contains(e.target)) closeList(false);
  });

  function activeIndex(){ return opts.findIndex(o => o.classList.contains('is-active')); }
  list.addEventListener('keydown', function (e) {
    let i = activeIndex();
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      i = Math.min(opts.length - 1, (i < 0 ? 0 : i + 1));
      opts.forEach(el => el.classList.remove('is-active'));
      opts[i].classList.add('is-active');
      opts[i].scrollIntoView({ block:'nearest' });
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      i = Math.max(0, (i < 0 ? 0 : i - 1));
      opts.forEach(el => el.classList.remove('is-active'));
      opts[i].classList.add('is-active');
      opts[i].scrollIntoView({ block:'nearest' });
    } else if (e.key === 'Home') {
      e.preventDefault();
      opts.forEach(el => el.classList.remove('is-active'));
      opts[0].classList.add('is-active');
      opts[0].scrollIntoView({ block:'nearest' });
    } else if (e.key === 'End') {
      e.preventDefault();
      const last = opts.at(-1);
      opts.forEach(el => el.classList.remove('is-active'));
      last.classList.add('is-active');
      last.scrollIntoView({ block:'nearest' });
    } else if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      const cur = opts[activeIndex()] || opts.find(el => el.classList.contains('is-selected'));
      if (cur) setByValue(cur.dataset.value || '');
      closeList(true);
    } else if (e.key === 'Escape') {
      e.preventDefault();
      closeList(true);
    }
  });

  native?.addEventListener('change', function () {
    setByValue(this.value || '');
  });
})();
</script>
@endsection
