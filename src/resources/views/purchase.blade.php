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
            <h1 class="purchase-item__title">商品購入</h1>
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

            <label for="payment_method" class="form-label"></label>
            <select id="payment_method" name="payment_method" class="form-select" required>
              <option value="" disabled {{ $initialPayment === '' || $initialPayment === null ? 'selected' : '' }}>選択してください</option>
              <option value="convenience_store" {{ $initialPayment === 'convenience_store' ? 'selected' : '' }}>コンビニ支払い</option>
              <option value="credit_card"       {{ $initialPayment === 'credit_card' ? 'selected' : '' }}>カード支払い</option>
            </select>
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
            <div class="address__row"><span class="muted"></span> {{ $recipient }}</div>
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
  var select  = document.getElementById('payment_method');
  var summary = document.getElementById('summary-payment');
  if (!select || !summary) return;

  function label(v) {
    if (v === 'credit_card') return 'カード支払い';
    if (v === 'convenience_store') return 'コンビニ支払い';
    return '未選択';
  }

  summary.textContent = label(select.value);
  select.addEventListener('change', function () {
    summary.textContent = label(this.value);
  });
  select.addEventListener('input', function () {
    summary.textContent = label(this.value);
  });
})();
</script>
@endsection
