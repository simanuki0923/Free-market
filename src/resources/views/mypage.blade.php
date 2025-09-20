@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection

@section('content')
<main class="product-list">
  <div class="container">

    {{-- タブ（スクショ準拠で見出しのみ） --}}
    <nav class="tab-buttons">
      <a href="#" class="tab-link active">おすすめ</a>
      @auth
        <a href="#" class="tab-link">マイリスト</a>
      @endauth
    </nav>

    {{-- 商品一覧 --}}
    <section class="product-list__section">
      <ul class="product-list__items">
        @forelse ($products as $product)
          @php
            // 画像URLの自動判定（http/https はそのまま、相対パスは storage リンク経由）
            $src = $product->image_url
                ? (\Illuminate\Support\Str::startsWith($product->image_url, ['http://','https://'])
                    ? $product->image_url
                    : asset('storage/'.$product->image_url))
                : asset('img/no-image.png'); // public/img/no-image.png を用意
          @endphp

        <li class="product-item">
             <a class="product-card" href="{{ route('product', ['product' => $product->id]) }}">
                <figure class="product-thumb">
                    <img src="{{ $src }}" alt="{{ $product->name }}">
                </figure>
              <div class="product-meta">
                <p class="product-name">{{ $product->name }}</p>
              </div>
            </a>
        </li>
        @empty
          <li class="product-empty">商品がありません。</li>
        @endforelse
      </ul>
    </section>

  </div>
</main>
@endsection
