{{-- resources/views/itemlist.blade.php --}}
@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/itemlist.css') }}">
@endsection

@section('content')
<main class="product-list" aria-label="マイリスト">
  <div class="container">

    @php
      // コントローラーから 'mylist' が渡ってきます（fallbackも用意）
      $tab = strtolower($tab ?? request('tab','mylist'));
      $isGuest = (bool)($isGuest ?? false);
    @endphp

    <nav class="tab-buttons">
      <a href="{{ route('item') }}"
         class="tab-link {{ $tab === 'all' ? 'active' : '' }}">おすすめ</a>
      <a href="{{ route('item', ['tab' => 'mylist']) }}"
         class="tab-link {{ $tab === 'mylist' ? 'active' : '' }}">マイリスト</a>
    </nav>

    {{-- 未認証は「何も表示しない」：文言・空UIも出さない --}}
    @if($isGuest)
      {{-- ここは完全に非表示（HTMLを出さない） --}}
    @else
      <section class="product-list__section">
        @if(($products ?? collect())->isEmpty())
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
                      <span class="sold-out-label">Sold</span>
                    @endif
                  </figure>
                  <div class="product-meta">
                    <h3 class="product-name">{{ $p->name }}</h3>
                  </div>
                </a>
              </li>
            @endforeach
          </ul>

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
    @endif

  </div>
</main>
@endsection
