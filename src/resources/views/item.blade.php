{{-- resources/views/item.blade.php --}}
@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/item.css') }}">
@endsection

@section('content')
@php
    // 表示タブ：URLの ?tab= を優先。未指定は 'all'
    $activeTab = strtolower(request('tab', 'all'));
    // 実データタブ：コントローラから渡された $tab（なければ 'all'）
    $dataTab   = strtolower($tab ?? 'all');

    $isLoggedIn = Auth::check();

    // 画像SRC決定ロジック（S3等の絶対URL／public/storage の相対パス両対応）
    $resolveImage = function ($product) {
        $path = $product->image_path ?? optional($product->sell)->image_path; // product優先→sellフォールバック
        if (!$path) {
            return asset('img/no-image.png');
        }
        return \Illuminate\Support\Str::startsWith($path, ['http://','https://'])
            ? $path
            : asset('storage/' . ltrim($path, '/'));
    };
@endphp

<main class="product-list" aria-label="{{ $activeTab === 'mylist' ? 'マイリスト' : 'おすすめ一覧' }}">
  <div class="container">

    {{-- タブナビ --}}
    <nav class="tab-buttons" role="tablist" aria-label="一覧切替タブ">
      <a href="{{ route('item') }}"
         class="tab-link {{ $activeTab === 'all' ? 'active' : '' }}"
         @if($activeTab === 'all') aria-current="page" @endif>おすすめ</a>

      <a href="{{ route('item', ['tab' => 'mylist']) }}"
         class="tab-link {{ $activeTab === 'mylist' ? 'active' : '' }}"
         @if($activeTab === 'mylist') aria-current="page" @endif>マイリスト</a>
    </nav>

    <section class="product-list__section">
      {{-- 未ログイン × マイリストタブ：商品は出さず案内のみ --}}
      @if ($activeTab === 'mylist' && !$isLoggedIn)
      @else
        @php
          $count = ($products ?? collect())->count();
          $isPaginator =
            $products instanceof \Illuminate\Contracts\Pagination\Paginator
            || $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
        @endphp

        @if ($count === 0)
          <p class="product-empty">
            {{ $dataTab === 'mylist' ? 'マイリストに商品がありません。' : '商品がありません。' }}
          </p>
        @else
          <ul class="product-list__items" role="list">
            @foreach($products as $p)
              @php
                $src    = $resolveImage($p);
                $isSold = (bool)($p->is_sold ?? false);
              @endphp

              <li class="product-item">
                <a href="{{ route('item.show', ['item_id' => $p->id]) }}" class="product-card">
                  <figure class="product-thumb">
                    <img src="{{ $src }}" alt="{{ $p->name }}">
                    @if($isSold)
                      <span class="sold-out-label" aria-label="売り切れ">Sold</span>
                    @endif
                  </figure>

                  <div class="product-meta">
                    <h3 class="product-name">{{ $p->name }}</h3>
                  </div>
                </a>
              </li>
            @endforeach
          </ul>

          @if($isPaginator && $products->hasPages())
            <div class="pagination">
              {{ $products->appends(request()->query())->links() }}
            </div>
          @endif
        @endif
      @endif
    </section>

  </div>
</main>
@endsection
