@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection

@section('content')
@php
  $user    = $user ?? auth()->user();
  $profile = optional($user)->profile;

  $displayName = optional($profile)->display_name ?? ($user->name ?? 'ユーザー名');

  $iconPath = !empty(optional($profile)->icon_image_path)
      ? asset('storage/'.ltrim($profile->icon_image_path, '/'))
      : asset('img/sample.jpg');

  $ratingRaw = isset($ratingAverage)
      ? (float) $ratingAverage
      : (float) (optional($profile)->rating ?? 0);

  $rating = (int) max(0, min(5, round($ratingRaw)));
  $ratingCount = (int) ($ratingCount ?? 0);

  $starOn  = asset('img/star_on.png');
  $starOff = asset('img/star_off.png');

  $active = in_array(($page ?? request()->query('page', 'sell')), ['sell','buy','trading'], true)
            ? ($page ?? request()->query('page', 'sell'))
            : 'sell';

  $resolveImage = function ($path) {
      if (!$path) return asset('img/no-image.png');
      return \Illuminate\Support\Str::startsWith($path, ['http://','https://'])
          ? $path
          : asset('storage/'.ltrim($path, '/'));
  };

  // 取引中タブの未読総数（Controllerで計算済みを優先）
  $tradingUnreadTotal = (int) ($tradingUnreadTotal ?? 0);
@endphp

<main class="mypage__main container">
  <section class="profile-section" aria-label="プロフィール">
    <figure class="profile-icon">
      <img src="{{ $iconPath }}" alt="">
    </figure>

    <div class="profile-info">
      <p class="user-name">{{ $displayName }}</p>

      <div class="user-rating" aria-label="ユーザー評価">
        @for ($i = 1; $i <= 5; $i++)
          <img
            src="{{ $i <= $rating ? $starOn : $starOff }}"
            alt="{{ $i <= $rating ? '評価あり' : '評価なし' }}"
            class="rating-star"
          >
        @endfor
      </div>

      <div class="user-rating-summary" aria-label="評価の概要">
        <span class="user-rating-average">{{ number_format($ratingRaw, 1) }}</span>
        <span class="user-rating-count">({{ $ratingCount }}件)</span>
      </div>
    </div>

    <a href="{{ route('mypage.profile') }}" class="edit-profile-btn">プロフィールを編集</a>
  </section>

  <nav class="toggle-links" aria-label="商品切替タブ">
    <a href="{{ route('mypage', ['page' => 'sell']) }}"
       class="toggle-link {{ $active==='sell' ? 'active' : '' }}">
      出品した商品
    </a>

    <a href="{{ route('mypage', ['page' => 'buy']) }}"
       class="toggle-link {{ $active==='buy' ? 'active' : '' }}">
      購入した商品
    </a>

    <a href="{{ route('mypage', ['page' => 'trading']) }}"
       class="toggle-link {{ $active==='trading' ? 'active' : '' }}">
      <span>取引中の商品</span>

      {{-- スクショ仕様: タブ右に赤い件数（小さめ） --}}
      @if ($tradingUnreadTotal > 0)
        <span class="trade-tab-count" aria-label="未読メッセージ件数 {{ $tradingUnreadTotal }} 件">
          {{ $tradingUnreadTotal }}
        </span>
      @endif
    </a>
  </nav>

  {{-- 出品 --}}
  @if ($active === 'sell')
    <section class="product-list" role="list">
      @forelse ($mySells ?? [] as $s)
        @php
          $thumb = $resolveImage($s->image_path ?? optional($s->product)->image_path);
          $pid   = $s->product_id ?? optional($s->product)->id;
          $name  = $s->name ?? optional($s->product)->name ?? '商品';
        @endphp

        <article class="product-item" role="listitem">
          @if ($pid)
            <a href="{{ route('item.show', ['item_id' => $pid]) }}" class="product-card">
          @else
            <div class="product-card" title="商品詳細（products）に未リンクです">
          @endif
              <figure class="product-thumb">
                <img src="{{ $thumb }}" alt="{{ $name }}">
                @if (!empty($s->is_sold))
                  <span class="sold-out-label">SOLD</span>
                @endif
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
        <div class="pager">
          {{ $mySells->links() }}
        </div>
      @endif
    </section>
  @endif

  {{-- 購入 --}}
  @if ($active === 'buy')
    <section class="product-list" role="list">
      @forelse ($purchasedProducts ?? [] as $p)
        @php
          $thumb = $resolveImage($p->image_path ?? null);
        @endphp

        <article class="product-item" role="listitem">
          <a href="{{ route('item.show', ['item_id' => $p->id]) }}" class="product-card">
            <figure class="product-thumb">
              <img src="{{ $thumb }}" alt="{{ $p->name }}">
              @if (!empty($p->is_sold))
                <span class="sold-out-label">SOLD</span>
              @endif
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
        <div class="pager">
          {{ $purchasedProducts->links() }}
        </div>
      @endif
    </section>
  @endif

  {{-- 取引中（スクショ仕様：カード左上に小さい赤丸数字） --}}
  @if ($active === 'trading')
    <section class="product-list" role="list">
      @forelse ($tradingProducts ?? [] as $t)
        @php
          $transactionId = $t->id ?? $t->transaction_id ?? null;

          $name  = $t->name ?? optional($t->product)->name ?? '商品';
          $thumb = $resolveImage($t->image_path ?? optional($t->product)->image_path);

          $unreadCount = (int) ($t->unread_messages_count ?? 0);

          $sellerId = (int) ($t->seller_id ?? 0);
          $buyerId  = (int) ($t->buyer_id ?? 0);
          $myId     = (int) (auth()->id() ?? 0);

          $chatUrl = null;
          if ($transactionId) {
              if ($sellerId !== 0 && $sellerId === $myId) {
                  $chatUrl = route('chat.seller', ['transaction' => $transactionId]);
              } elseif ($buyerId !== 0 && $buyerId === $myId) {
                  $chatUrl = route('chat.buyer', ['transaction' => $transactionId]);
              }
          }
        @endphp

        <article class="product-item" role="listitem">
          @if ($chatUrl)
            <a href="{{ $chatUrl }}" class="product-card">
          @else
            <div class="product-card" title="取引チャットに遷移できません">
          @endif

              <figure class="product-thumb product-thumb--trading">
                <img src="{{ $thumb }}" alt="{{ $name }}">

                {{-- スクショ仕様：左上の小さい赤丸数字 --}}
                @if ($unreadCount > 0)
                  <span class="trade-unread-dot" aria-label="未読メッセージ {{ $unreadCount }} 件">
                    {{ $unreadCount }}
                  </span>
                @endif
              </figure>

              <div class="product-meta">
                <p class="product-name">{{ $name }}</p>
              </div>

          @if ($chatUrl)</a>@else</div>@endif
        </article>
      @empty
        <p class="empty-note">取引中の商品はありません。</p>
      @endforelse

      @if (isset($tradingProducts) && $tradingProducts instanceof \Illuminate\Contracts\Pagination\Paginator && $tradingProducts->hasPages())
        <div class="pager">
          {{ $tradingProducts->links() }}
        </div>
      @endif
    </section>
  @endif
</main>
@endsection