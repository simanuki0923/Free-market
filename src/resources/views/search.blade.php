@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/search.css') }}">
@endsection

@section('content')
@php
  use Illuminate\Support\Str;

  $activeTab = strtolower($tab ?? request('tab', 'all'));

  $resolveImage = function ($model) {
      $raw = $model->image_url ?? $model->image_path ?? null;
      if (!$raw) return asset('img/no-image.png');
      return Str::startsWith($raw, ['http://','https://'])
          ? $raw
          : asset('storage/'.ltrim($raw, '/'));
  };

  $isPaginator = function ($v) {
      return $v instanceof \Illuminate\Contracts\Pagination\Paginator
          || $v instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
  };
@endphp

<main class="product-list" aria-label="{{ $activeTab === 'mylist' ? 'マイリスト' : 'おすすめ' }}">
  <div class="container">

    <nav class="tab-buttons" aria-label="表示切替タブ">
      <a href="{{ route('item', array_merge(request()->query(), ['tab' => 'all'])) }}"
         class="tab-link {{ $activeTab === 'all' ? 'active' : '' }}"
         @if($activeTab === 'all') aria-current="page" @endif>おすすめ</a>

      <a href="{{ route('item', array_merge(request()->query(), ['tab' => 'mylist'])) }}"
         class="tab-link {{ $activeTab === 'mylist' ? 'active' : '' }}"
         @if($activeTab === 'mylist') aria-current="page" @endif>マイリスト</a>
    </nav>

    <section class="product-list__section">
      @php $count = ($products ?? collect())->count(); @endphp

      @if ($count === 0)
        <p class="product-empty">
          {{ $activeTab === 'mylist' ? 'マイリストに商品がありません。' : '商品がありません。' }}
        </p>
      @else
        <ul class="product-list__items" role="list">
          @foreach($products as $p)
            @php
              $src    = $resolveImage($p);
              $isSold = (bool)($p->is_sold ?? false);
            @endphp
            <li class="product-item" role="listitem">
              <a href="{{ route('item.show', ['item_id' => $p->id]) }}" class="product-card">
                <figure class="product-thumb">
                  <img src="{{ $src }}" alt="{{ $p->name }}">
                  @if($isSold)
                    <span class="sold-out-label">sold</span>
                  @endif
                </figure>
                <div class="product-meta">
                  <h3 class="product-name">{{ $p->name }}</h3>
                </div>
              </a>
            </li>
          @endforeach
        </ul>

        @if ($isPaginator($products) && $products->hasPages())
          <div class="pagination" style="margin-top:24px">
            {{ $products->appends(request()->query())->links() }}
          </div>
        @endif
      @endif
    </section>

  </div>
</main>
@endsection
