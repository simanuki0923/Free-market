@extends('layouts.app')

@section('css')
  {{-- 既存の item.css を読み込む想定（グリッドやカードの見た目） --}}
  <link rel="stylesheet" href="{{ asset('css/item.css') }}">
@endsection

@section('content')
<main class="product-list">
  <div class="container">

    {{-- タブナビ（?tab の値で赤文字の位置だけ変える） --}}
    @php $tab = strtolower(request('tab', $tab ?? 'all')); @endphp
    <nav class="tab-buttons">
      <a href="{{ route('item') }}"
        class="tab-link {{ $tab === 'all' ? 'active' : '' }}">おすすめ</a>
      <a href="{{ route('item', ['tab' => 'mylist']) }}"
        class="tab-link {{ $tab === 'mylist' ? 'active' : '' }}">マイリスト</a>
    </nav>

    <section class="product-list__section">
      @if($products->count() === 0)
        <p class="product-empty">商品がありません。</p>
      @else
        <ul class="product-list__items">
          @foreach($products as $p)
            @php
              $src = $p->image_url
                ? (\Illuminate\Support\Str::startsWith($p->image_url, ['http://','https://'])
                    ? $p->image_url
                    : asset('storage/'.$p->image_url))
                : asset('img/no-image.png');
              $isSold = (bool)($p->is_sold ?? false);
              $isFav  = in_array($p->id, $myFavoriteIds ?? [], true);
            @endphp

            <li class="product-item">
              <a href="{{ route('item.show', ['item_id' => $p->id]) }}" class="product-card">
                <figure class="product-thumb">
                  <img src="{{ $src }}" alt="{{ $p->name }}">
                  @if($isSold)
                    <span class="sold-out-label">SOLD</span>
                  @endif
                </figure>
                <div class="product-meta">
                  <h3 class="product-name">{{ $p->name }}</h3>
                  {{-- 価格やお気に入りUIを出したい場合はここに追加 --}}
                </div>
              </a>
            </li>
          @endforeach
        </ul>

        {{-- ページネーション（全商品は常に Paginator 想定） --}}
        @if($products->hasPages())
          <div class="pagination">
            {{ $products->appends(request()->query())->links() }}
          </div>
        @endif
      @endif
    </section>

  </div>
</main>
@endsection
