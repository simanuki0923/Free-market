{{-- resources/views/mypage.blade.php --}}
@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection

@section('content')
<main class="mypage__main container">

  {{-- プロフィールヘッダー --}}
  <section class="profile-section" aria-label="プロフィール">
    <figure class="profile-icon" aria-hidden="true">
      <img
        src="{{ ($user->profile && $user->profile->icon_image_path)
                ? asset('storage/'.$user->profile->icon_image_path)
                : asset('img/sample.jpg') }}"
        alt="{{ ($user_name ?? $user->name).' のアイコン' }}">
    </figure>

    <div class="profile-info">
      <div class="profile-info-content">
        <p class="user-name">{{ $user_name ?? $user->name }}</p>
      </div>
      <a href="{{ route('profile.edit') }}" class="edit-profile-btn">プロフィールを編集</a>
    </div>
  </section>

  {{-- タブ（出品した商品 / 購入した商品） --}}
  <nav class="toggle-links" aria-label="商品切替タブ">
    <a href="javascript:void(0);" id="toggleListed" class="toggle-link active" role="button" aria-pressed="true">
      出品した商品
    </a>
    <a href="javascript:void(0);" id="togglePurchased" class="toggle-link" role="button" aria-pressed="false">
      購入した商品
    </a>
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
                <span class="sold-out-label" aria-label="売り切れ">sold out</span>
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

  {{-- 購入した商品（初期は非表示） --}}
  <section id="purchasedProducts" class="product-list" role="list" style="display:none;">
    @if ($purchasedProducts->isNotEmpty())
      @foreach ($purchasedProducts as $product)
        @php
          // 購入履歴は配列でもコレクションでも対応できるようにアクセサ
          $pid   = is_array($product) ? $product['id']        : $product->id;
          $pname = is_array($product) ? ($product['name'] ?? '') : ($product->name ?? '');
          $pimg  = is_array($product) ? ($product['image_url'] ?? null) : ($product->image_url ?? null);
          $thumb = $pimg
            ? (\Illuminate\Support\Str::startsWith($pimg, ['http://','https://'])
                ? $pimg
                : asset('storage/'.$pimg))
            : asset('img/no-image.png');
        @endphp

        <article class="product-item" role="listitem">
          <a href="{{ route('item.show', ['item_id' => $pid]) }}" class="product-card" aria-label="{{ $pname }}">
            <figure class="product-thumb">
              <img src="{{ $thumb }}" alt="{{ $pname }}">
            </figure>
            <div class="product-meta">
              <p class="product-name">{{ $pname }}</p>
            </div>
          </a>
        </article>
      @endforeach
    @else
      <p class="empty-note">購入した商品はありません。</p>
    @endif
  </section>

</main>

{{-- タブ切替スクリプト --}}
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

    // 初期表示（出品した商品）
    showListed();
  })();
</script>
@endsection
