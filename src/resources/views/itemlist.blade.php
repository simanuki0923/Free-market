@extends('layouts.app')

@section('css')
  {{-- 提示CSSに合わせて itemlist.css or 共有の item.css を読み込み --}}
  <link rel="stylesheet" href="{{ asset('css/itemlist.css') }}">
@endsection

@section('content')
<main class="product-list">
  <div class="container">

    {{-- タブ（CSSに合わせて .tab-buttons / .tab-link を使用） --}}
    @php $tab = strtolower(request('tab', $tab ?? 'mylist')); @endphp
    <nav class="tab-buttons">
      <a href="{{ route('item') }}"
        class="tab-link {{ $tab === 'all' ? 'active' : '' }}">おすすめ</a>
      <a href="{{ route('item', ['tab' => 'mylist']) }}"
        class="tab-link {{ $tab === 'mylist' ? 'active' : '' }}">マイリスト</a>
    </nav>

    <section class="product-list__section">
      {{-- ゲスト案内 --}}
      @if(($isGuest ?? false) === true)
        <p class="product-empty">
          マイリストを利用するにはログインしてください。<br>
          <a href="{{ route('login') }}">ログイン</a> ／
          <a href="{{ route('register') }}">会員登録</a>
        </p>
      @else
        {{-- 一覧グリッド --}}
        @if($products->isEmpty())
          <p class="product-empty">マイリストに商品がありません。</p>
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
                    {{-- 価格／お気に入りUIは仕様により未表示 --}}
                  </div>
                </a>
              </li>
            @endforeach
          </ul>
        @endif

        {{-- ページネーション（Collection時はlinks不可のためガード） --}}
        @php
          $isPaginator =
            $products instanceof \Illuminate\Contracts\Pagination\Paginator
            || $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
        @endphp
        @if($isPaginator && $products->hasPages())
          <div class="pagination">
            {{ $products->appends(request()->query())->links() }}
          </div>
        @endif
      @endif
    </section>

  </div>
</main>
@endsection
