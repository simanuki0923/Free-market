@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('content')
@php
  // 画像URL（外部URL / storage / no-image）
  $src = $product->image_url
      ? (\Illuminate\Support\Str::startsWith($product->image_url, ['http://','https://'])
          ? $product->image_url
          : asset('storage/'.$product->image_url))
      : asset('img/no-image.png');

  // 住所スナップショット用（プロフィールからの初期表示）
  $recipient   = optional($profile)->display_name ?? auth()->user()->name ?? 'お名前未設定';
  $postal      = optional($profile)->zip_code     ?? '〒----';
  $pref        = optional($profile)->prefecture   ?? '';
  $city        = optional($profile)->city         ?? '';
  $addr1       = optional($profile)->address_line1?? '';
  $addr2       = optional($profile)->address_line2?? '';
  $phone       = optional($profile)->phone        ?? '';
  $addressDisp = trim("$pref $city $addr1 $addr2");
@endphp

<main class="purchase__main container">
  @if (session('status'))
    <div class="flash success" role="status">{{ session('status') }}</div>
  @endif
  @if (session('error'))
    <div class="flash error" role="alert">{{ session('error') }}</div>
  @endif

  <div class="purchase__grid">
    {{-- 左カラム：商品と入力 --}}
    <section class="purchase__left">
      {{-- 商品概要 --}}
      <article class="purchase-item card">
        <header class="card__header">
          <h1 class="card__title">商品購入</h1>
        </header>
        <div class="purchase-item__body">
          <figure class="purchase-item__thumb">
            <img src="{{ $src }}" alt="{{ $product->name ?? '商品画像' }}">
          </figure>
          <div class="purchase-item__info">
            <h2 class="purchase-item__name">{{ $product->name }}</h2>
            <p class="purchase-item__price">¥ {{ number_format($product->price) }}</p>
          </div>
        </div>
      </article>

      {{-- 支払い方法 --}}
      <article class="card">
        <header class="card__header">
          <h2 class="card__title">支払い方法</h2>
        </header>
        <div class="card__section">
          <form id="payment-form" action="{{ route('payment.create') }}" method="GET" class="purchase-form">
            {{-- 画面右の「購入する」ボタンから submit します --}}
            <input type="hidden" name="item_id" value="{{ $product->id }}">

            <label for="payment_method" class="form-label">支払い方法を選択</label>
            <select id="payment_method" name="payment_method" class="form-select" required>
              <option value="" disabled {{ empty(optional($payment)->payment_method) ? 'selected' : '' }}>
                選択してください
              </option>
              <option value="convenience_store"
                {{ (optional($payment)->payment_method ?? '') === 'convenience_store' ? 'selected' : '' }}>
                コンビニ払い
              </option>
              <option value="credit_card"
                {{ (optional($payment)->payment_method ?? '') === 'credit_card' ? 'selected' : '' }}>
                クレジットカード
              </option>
            </select>
          </form>
        </div>
      </article>

      {{-- 配送先 --}}
      <article class="card">
        <header class="card__header card__header--with-action">
          <h2 class="card__title">配送先</h2>
          <a class="btn" href="{{ route('purchase.address.edit', ['item_id' => $product->id]) }}">変更する</a>
        </header>
        <div class="card__section">
          <div class="address">
            <div class="address__row"><span class="muted">お届け先</span> {{ $recipient }}</div>
            <div class="address__row"><span class="muted">郵便番号</span> {{ $postal }}</div>
            <div class="address__row"><span class="muted">住所</span> {{ $addressDisp ?: 'ここに住所が表示されます' }}</div>
            @if($phone)
              <div class="address__row"><span class="muted">電話</span> {{ $phone }}</div>
            @endif
          </div>
        </div>
      </article>
    </section>

    {{-- 右カラム：サマリと購入ボタン --}}
    <aside class="purchase__right">
      <div class="summary card">
        <header class="card__header">
          <h2 class="card__title">お支払い</h2>
        </header>
        <div class="card__section summary__row">
          <span>商品代金</span>
          <strong>¥ {{ number_format($product->price) }}</strong>
        </div>
        <div class="card__section summary__row">
          <span>支払い方法</span>
          <span id="summary-payment">
            {{-- セレクトの現在値を表示（JSで動的連動） --}}
            @php
              $pm = optional($payment)->payment_method;
              $pmText = $pm === 'credit_card' ? 'クレジットカード' : ($pm === 'convenience_store' ? 'コンビニ払い' : '未選択');
            @endphp
            {{ $pmText }}
          </span>
        </div>
        <div class="card__section">
          {{-- 右側の購入ボタンで左フォームを submit --}}
          <button type="submit" form="payment-form" class="purchase-button--primary" {{ $product->is_sold ? 'disabled' : '' }}>
            購入する
          </button>
          @if ($product->is_sold)
            <p class="form-help">この商品は既にSOLDです。</p>
          @endif
        </div>
      </div>
    </aside>
  </div>
</main>

{{-- 画面内スクリプト（支払い方法の表示を右側サマリに反映） --}}
@push('scripts')
<script>
  (function(){
    var select = document.getElementById('payment_method');
    var summary = document.getElementById('summary-payment');
    if(!select || !summary) return;
    var label = function(v){
      if (v === 'credit_card') return 'クレジットカード';
      if (v === 'convenience_store') return 'コンビニ払い';
      return '未選択';
    };
    select.addEventListener('change', function(){
      summary.textContent = label(this.value);
    });
  })();
</script>
@endpush
@endsection