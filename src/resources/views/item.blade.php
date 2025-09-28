@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/item.css') }}">
@endsection

@section('content')
@php
  // 表示用（URLのtabをそのまま採用。未指定は 'all'）
  $activeTab = strtolower(request('tab', 'all'));

  // データ用（コントローラから渡された $tab を優先：実データはこれで取得されている想定）
  $dataTab = strtolower($tab ?? 'all');

  // ログイン可否（Blade側で判定）
  $isLoggedIn = Auth::check();
@endphp

<main class="product-list" aria-label="{{ $activeTab === 'mylist' ? 'マイリスト' : 'おすすめ一覧' }}">
  <div class="container">

    <nav class="tab-buttons">
      <a href="{{ route('item') }}"
         class="tab-link {{ $activeTab === 'all' ? 'active' : '' }}"
         @if($activeTab === 'all') aria-current="page" @endif>おすすめ</a>

      <a href="{{ route('item', ['tab' => 'mylist']) }}"
         class="tab-link {{ $activeTab === 'mylist' ? 'active' : '' }}"
         @if($activeTab === 'mylist') aria-current="page" @endif>マイリスト</a>
    </nav>

    <section class="product-list__section">
      {{-- ▼ 未ログイン × マイリストタブ：商品は非表示、案内のみ --}}
      @if ($activeTab === 'mylist' && !$isLoggedIn)
        <div class="product-empty" role="status" style="padding:16px; border:1px solid #e5e7eb; border-radius:8px;">
          マイリストの表示にはログインが必要です。
          <a href="{{ route('login') }}" class="btn btn-primary" style="margin-left:8px;">ログイン</a>
        </div>

      {{-- ▼ 通常描画（おすすめ or ログイン済みのマイリスト） --}}
      @else
        @php
          $count = ($products ?? collect())->count();
        @endphp

        @if ($count === 0)
          <p class="product-empty">
            {{-- メッセージはデータ実態（$dataTab）に合わせる --}}
            {{ $dataTab === 'mylist' ? 'マイリストに商品がありません。' : '商品がありません。' }}
          </p>
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
      @endif
    </section>

  </div>
</main>
@endsection
