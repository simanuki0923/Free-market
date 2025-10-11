{{-- resources/views/mypage.blade.php --}}
@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
  <style>
    .sold-out-label{position:absolute;top:8px;left:8px;background:#000;color:#fff;padding:2px 6px;font-size:12px;border-radius:4px}
  </style>
@endsection

@section('content')
@php
  // ユーザー / プロフィール
  $user    = $user ?? auth()->user();
  $profile = optional($user)->profile;

  // 表示名（display_name が無ければ user->name）
  $displayName = optional($profile)->display_name ?? ($user->name ?? 'ユーザー');

  // アイコン
  $iconPath = !empty(optional($profile)->icon_image_path)
      ? asset('storage/'.ltrim($profile->icon_image_path, '/'))
      : asset('img/sample.jpg');

  // タブ（?page=sell|buy 以外は sell）
  $active = in_array(($page ?? request()->query('page', 'sell')), ['sell','buy'], true)
            ? ($page ?? 'sell') : 'sell';

  // 画像URL解決（absolute / storage / no-image）
  $resolveImage = function ($path) {
      if (!$path) return asset('img/no-image.png');
      return \Illuminate\Support\Str::startsWith($path, ['http://','https://'])
          ? $path
          : asset('storage/'.ltrim($path, '/'));
  };
@endphp

<main class="mypage__main container">
  <section class="profile-section" aria-label="プロフィール">
    <figure class="profile-icon">
      <img src="{{ $iconPath }}" alt="{{ $displayName }} のアイコン">
    </figure>
    <div class="profile-info">
      <p class="user-name">{{ $displayName }}</p>
      {{-- ルート名は環境に合わせて --}}
      <a href="{{ route('mypage.profile') }}" class="edit-profile-btn">プロフィールを編集</a>
    </div>
  </section>

  <nav class="toggle-links" aria-label="商品切替タブ">
    <a href="{{ route('mypage', ['page' => 'sell']) }}" class="toggle-link {{ $active==='sell' ? 'active' : '' }}">出品した商品</a>
    <a href="{{ route('mypage', ['page' => 'buy'])  }}" class="toggle-link {{ $active==='buy'  ? 'active' : '' }}">購入した商品</a>
  </nav>

  {{-- 出品した商品（sells -> products 詳細へ） --}}
  @if ($active === 'sell')
    <section class="product-list" role="list" style="margin-top:16px">
      @forelse ($mySells ?? [] as $s)
        @php
          $thumb = $resolveImage($s->image_path ?? optional($s->product)->image_path);
          $pid   = $s->product_id ?? optional($s->product)->id; // 詳細は product の id
          $name  = $s->name ?? optional($s->product)->name ?? '商品';
        @endphp

        <article class="product-item" role="listitem">
          @if ($pid)
            <a href="{{ route('item.show', ['item_id' => $pid]) }}" class="product-card">
          @else
            <div class="product-card" title="商品詳細（products）に未リンクです">
          @endif
              <figure class="product-thumb" style="position:relative">
                <img src="{{ $thumb }}" alt="{{ $name }}">
                @if (!empty($s->is_sold)) <span class="sold-out-label">sold</span> @endif
              </figure>
              <div class="product-meta">
                <p class="product-name">{{ $name }}</p>
              </div>
          @if ($pid)</a>@else</div>@endif
        </article>
      @empty
        <p class="empty-note">出品した商品はありません。</p>
      @endforelse

      @if (isset($mySells) && $mySells instanceof \Illuminate\Contracts\Pagination\Paginator && $mySells->hasPages())
        <div class="pager" style="margin-top:12px">
          {{ $mySells->links() }} {{-- p1（Controllerで appends 済み） --}}
        </div>
      @endif
    </section>
  @endif

  {{-- 購入した商品（purchases → sells → products） --}}
  @if ($active === 'buy')
    <section class="product-list" role="list" style="margin-top:16px">
      @forelse ($purchasedProducts ?? [] as $p)
        @php
          // Controllerのselectで image_path / purchased_amount / purchased_at を受け取っている前提
          $thumb = $resolveImage($p->image_path ?? null);
        @endphp

        <article class="product-item" role="listitem">
          <a href="{{ route('item.show', ['item_id' => $p->id]) }}" class="product-card">
            <figure class="product-thumb" style="position:relative">
              <img src="{{ $thumb }}" alt="{{ $p->name }}">
              @if (!empty($p->is_sold)) <span class="sold-out-label">sold</span> @endif
            </figure>
            <div class="product-meta">
              <p class="product-name">{{ $p->name }}</p>
            </div>
          </a>
        </article>
      @empty
        <p class="empty-note">購入した商品はありません。</p>
      @endforelse

      @if (isset($purchasedProducts) && $purchasedProducts instanceof \Illuminate\Contracts\Pagination\Paginator && $purchasedProducts->hasPages())
        <div class="pager" style="margin-top:12px">
          {{ $purchasedProducts->links() }} {{-- p2（Controllerで appends 済み） --}}
        </div>
      @endif
    </section>
  @endif
</main>
@endsection
