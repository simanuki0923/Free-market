@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection

@section('content')
@php
  $displayName = $displayName ?? ($user->profile->display_name ?? $user->name ?? 'ユーザー');
  $iconPath = ($user->profile && $user->profile->icon_image_path)
      ? asset('storage/'.$user->profile->icon_image_path)
      : asset('img/sample.jpg');
@endphp

<main class="mypage__main container">

  {{-- プロフィールヘッダー --}}
  <section class="profile-section" aria-label="プロフィール">
    <figure class="profile-icon" aria-hidden="true">
      <img src="{{ $iconPath }}" alt="{{ $displayName.' のアイコン' }}">
    </figure>

    <div class="profile-info">
      <div class="profile-info-content">
        <p class="user-name">{{ $displayName }}</p>
      </div>
      <a href="{{ route('mypage.profile') }}" class="edit-profile-btn">プロフィールを編集</a>
    </div>
  </section>

  {{-- タブ（出品した商品 / 購入した商品） --}}
  <nav class="toggle-links" aria-label="商品切替タブ">
    <a href="javascript:void(0);" id="toggleListed" class="toggle-link active" role="button" aria-pressed="true">出品した商品</a>
    <a href="javascript:void(0);" id="togglePurchased" class="toggle-link" role="button" aria-pressed="false">購入した商品</a>
  </nav>

  {{-- 出品した商品 --}}
  <section id="listedProducts" class="product-list" role="list">
    @if ($listedProducts->isNotEmpty())
      @foreach ($listedProducts as $product)
        @php
          $thumb = $product->image_url
            ? (\Illuminate\Support\Str::startsWith($product->image_url, ['http://','https://'])
                ? $product->image_url
                : asset('storage/'.$product->image_url))
            : asset('img/no-image.png');
        @endphp
        <article class="product-item" role="listitem">
          <a href="{{ route('item.show', ['item_id' => $product->id]) }}" class="product-card" aria-label="{{ $product->name }}">
            <figure class="product-thumb">
              <img src="{{ $thumb }}" alt="{{ $product->name }}">
              @if ($product->is_sold)
                <span class="sold-out-label" aria-label="売り切れ">sold</span>
              @endif
            </figure>
            <div class="product-meta">
              <p class="product-name">{{ $product->name }}</p>
            </div>
          </a>
        </article>
      @endforeach
    @else
      <p class="empty-note">出品した商品はありません。</p>
    @endif
  </section>

  {{-- 購入した商品 --}}
  <section id="purchasedProducts" class="product-list" role="list" style="display:none;">
    @if ($purchasedProducts->isNotEmpty())
      @foreach ($purchasedProducts as $product)
        @php
          // コレクション要素が配列/オブジェクトどちらでも動くように
          $pid   = is_array($product) ? $product['id'] : ($product->id ?? null);
          $pname = is_array($product) ? ($product['name'] ?? '') : ($product->name ?? '');
          $pimg  = is_array($product) ? ($product['image_url'] ?? null) : ($product->image_url ?? null);
          $thumb = $pimg
            ? (\Illuminate\Support\Str::startsWith($pimg, ['http://','https://']) ? $pimg : asset('storage/'.$pimg))
            : asset('img/no-image.png');

          $pstatus = is_array($product) ? ($product['purchase_status'] ?? null) : ($product->purchase_status ?? null);
          $pdate   = is_array($product) ? ($product['purchased_at'] ?? null)   : ($product->purchased_at ?? null);
        @endphp

        <article class="product-item" role="listitem">
          <a href="{{ route('item.show', ['item_id' => $pid]) }}" class="product-card" aria-label="{{ $pname }}">
            <figure class="product-thumb">
              <img src="{{ $thumb }}" alt="{{ $pname }}">
            </figure>
            <div class="product-meta">
              <p class="product-name">{{ $pname }}</p>
              @if($pstatus || $pdate)
              @endif
            </div>
          </a>
        </article>
      @endforeach
    @else
      <p class="empty-note">購入した商品はありません。</p>
    @endif
  </section>

</main>

<script>
  (function () {
    const $listed     = document.getElementById('listedProducts');
    const $purchased  = document.getElementById('purchasedProducts');
    const $btnListed  = document.getElementById('toggleListed');
    const $btnBought  = document.getElementById('togglePurchased');

    function showListed() {
      $listed.style.display = 'grid';
      $purchased.style.display = 'none';
      $btnListed.classList.add('active');
      $btnBought.classList.remove('active');
      $btnListed.setAttribute('aria-pressed','true');
      $btnBought.setAttribute('aria-pressed','false');
    }
    function showPurchased() {
      $listed.style.display = 'none';
      $purchased.style.display = 'grid';
      $btnListed.classList.remove('active');
      $btnBought.classList.add('active');
      $btnListed.setAttribute('aria-pressed','false');
      $btnBought.setAttribute('aria-pressed','true');
    }

    $btnListed.addEventListener('click', showListed);
    $btnBought.addEventListener('click', showPurchased);
    showListed();
  })();
</script>
@endsection
