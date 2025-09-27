@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
  <style>
    .sold-out-label{position:absolute;top:8px;left:8px;background:#000;color:#fff;padding:2px 6px;font-size:12px;border-radius:4px}
  </style>
@endsection

@section('content')
@php
  // ユーザー / プロフィール取得（null安全）
  $user = $user ?? auth()->user();
  $profile = optional($user)->profile;

  // display_name は null の可能性があるため optional() か ?-> を使う
  $displayName = optional($profile)->display_name ?? ($user->name ?? 'ユーザー');

  // アイコン（storage/フォールバック）
  $iconPath = ($profile && $profile->icon_image_path)
      ? asset('storage/'.$profile->icon_image_path)
      : asset('img/sample.jpg');

  // タブ（?page=sell|buy を見る。その他値は sell にフォールバック）
  $raw = $page ?? request()->query('page', 'sell');
  $active = in_array($raw, ['sell','buy'], true) ? $raw : 'sell';
@endphp

<main class="mypage__main container">
  <section class="profile-section" aria-label="プロフィール">
    <figure class="profile-icon">
      <img src="{{ $iconPath }}" alt="{{ $displayName }} のアイコン">
    </figure>
    <div class="profile-info">
      <p class="user-name">{{ $displayName }}</p>
      {{-- ルート名はあなたの環境に合わせて（例: profile.edit / mypage.profile など） --}}
      <a href="{{ route('mypage.profile') }}" class="edit-profile-btn">プロフィールを編集</a>
    </div>
  </section>

  <nav class="toggle-links" aria-label="商品切替タブ">
    <a href="{{ route('mypage', ['page' => 'sell']) }}" class="toggle-link {{ $active==='sell' ? 'active' : '' }}">出品した商品</a>
    <a href="{{ route('mypage', ['page' => 'buy']) }}"  class="toggle-link {{ $active==='buy'  ? 'active' : '' }}">購入した商品</a>
  </nav>

  {{-- 出品した商品（sells -> products 詳細へ） --}}
  @if ($active === 'sell')
    <section class="product-list" role="list" style="margin-top:16px">
      @forelse ($mySells as $s)
        @php
          $thumb = method_exists($s, 'getThumbUrlAttribute')
                    ? $s->thumb_url
                    : ($s->image_path
                        ? (\Illuminate\Support\Str::startsWith($s->image_path, ['http://','https://'])
                            ? $s->image_path
                            : asset('storage/'.$s->image_path))
                        : asset('img/no-image.png'));
          // ← 詳細に飛ばすのは Product の id
          $pid = $s->product_id ?? optional($s->product)->id;
        @endphp

        <article class="product-item" role="listitem">
          @if ($pid)
            <a href="{{ route('item.show', ['item_id' => $pid]) }}" class="product-card">
          @else
            <div class="product-card" title="商品詳細（products）に未リンクです">
          @endif

              <figure class="product-thumb" style="position:relative">
                <img src="{{ $thumb }}" alt="{{ $s->name }}">
                @if ($s->is_sold) <span class="sold-out-label">sold</span> @endif
              </figure>
              <div class="product-meta">
                <p class="product-name">{{ $s->name }}</p>
              </div>

          @if ($pid)</a>@else</div>@endif
        </article>
      @empty
        <p class="empty-note">出品した商品はありません。</p>
      @endforelse

      <div class="pager" style="margin-top:12px">
        {{ $mySells->withQueryString()->links() }}
      </div>
    </section>
  @endif

  {{-- 購入した商品（purchases 経由） --}}
  @if ($active === 'buy')
    <section class="product-list" role="list">
      @forelse ($purchasedProducts as $p)
        @php
          $thumb = $p->image_url
                      ? (\Illuminate\Support\Str::startsWith($p->image_url, ['http://','https://'])
                          ? $p->image_url
                          : asset('storage/'.$p->image_url))
                      : asset('img/no-image.png');
        @endphp
        <article class="product-item" role="listitem">
          <a href="{{ route('item.show', ['item_id' => $p->id]) }}" class="product-card">
            <figure class="product-thumb" style="position:relative">
              <img src="{{ $thumb }}" alt="{{ $p->name }}">
              @if ($p->is_sold) <span class="sold-out-label">sold</span> @endif
            </figure>
            <div class="product-meta">
              <p class="product-name">{{ $p->name }}</p>
            </div>
          </a>
        </article>
      @empty
        <p class="empty-note">購入した商品はありません。</p>
      @endforelse

      <div class="pager" style="margin-top:12px">
        {{ $purchasedProducts->withQueryString()->links() }}
      </div>
    </section>
  @endif
</main>
@endsection
