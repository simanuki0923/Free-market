@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/product.css') }}">
@endsection

@section('content')
<main class="product-detail__main container">
  @php
    $src = $product->image_url;

    if (!$src && optional($product->sell)->image_path) {
        $src = $product->sell->image_path; // ★ 出品時の画像を利用
    }

    if (!$src) {
        $src = asset('img/no-image.png');
    } else {
        $src = \Illuminate\Support\Str::startsWith($src, ['http://', 'https://'])
            ? $src
            : asset('storage/' . ltrim($src, '/'));
    }

    $favoritesCount = $product->favorites_count ?? ($product->favoredByUsers()->count() ?? 0);
    $commentsCount  = $commentsCount ?? ($product->comments_count ?? ($product->comments()->count() ?? 0));

    $isFavorited = $isFavorited ?? false;
  @endphp

  <article class="product-detail__container">
    <figure class="product-detail__image">
      <img src="{{ $src }}" alt="{{ $product->name ?? '商品画像' }}">
    </figure>

    <div class="product-pane">
      <section class="product-detail__info">
        <h2 class="product-title">{{ $product->name ?? '商品名がありません' }}</h2>
        <p class="brand">{{ $product->brand ?? 'ブランド情報がありません' }}</p>

        <p class="price">
          @if (!is_null($product->price))
            ¥{{ number_format((int) $product->price) }} <small>（税込）</small>
          @else
            価格が設定されていません
          @endif
        </p>

        <aside class="action-buttons">
          @auth
            <form action="{{ route('product.favorite.toggle', ['product' => $product->id]) }}"
                  method="POST"
                  style="display:inline;">
              @csrf
              <button type="submit"
                      class="favorite-button {{ $isFavorited ? 'favorited' : '' }}"
                      aria-label="お気に入りに追加/解除">
                <img class="iconstar" src="{{ asset('img/star.png') }}" alt="お気に入り" aria-hidden="true">
                <span class="action-count" id="favorite-count">{{ $favoritesCount }}</span>
              </button>
            </form>
          @endauth

          @guest
            <a href="{{ route('login') }}"
               class="favorite-button"
               aria-label="ログインしてお気に入りに追加">
              <img class="iconstar" src="{{ asset('img/star.png') }}" alt="お気に入り" aria-hidden="true">
              <span class="action-count" id="favorite-count">{{ $favoritesCount }}</span>
            </a>
          @endguest

          <span class="comment-counter" aria-label="コメント数">
            <img class="iconcomment" src="{{ asset('img/comment.png') }}" alt="コメント" aria-hidden="true">
            <span class="action-count" id="comment-count">{{ $commentsCount }}</span>
          </span>
        </aside>

        <div class="action-right">
          @if(!$product->is_sold && (!auth()->check() || $product->user_id !== auth()->id()))
            <a href="{{ route('purchase', ['item_id' => $product->id]) }}" class="purchase-button">
              購入する
            </a>
          @elseif(auth()->check() && $product->user_id === auth()->id())
            <button class="purchase-button" disabled>自分の商品は購入できません</button>
          @else
            <button class="purchase-button" disabled>SOLD</button>
          @endif
        </div>

        {{-- 商品説明 / 商品情報 --}}
        <div class="product-info-item">
          <strong>商品説明</strong>
          <p class="description">{{ $product->description ?? '説明がありません' }}</p>

          <strong>商品の情報</strong>
          @php
            $categoryNames = collect();
            if (method_exists($product, 'categories') && $product->relationLoaded('categories') && $product->categories->count()) {
              $categoryNames = $product->categories->pluck('name');
            } elseif (!empty($product->category) && !empty($product->category->name)) {
              $categoryNames = collect([$product->category->name]);
            }
          @endphp
          <p class="category">
            カテゴリー：
            @if ($categoryNames->count())
              {{ $categoryNames->join(' / ') }}
            @else
              設定なし
            @endif
          </p>
          <p class="condition">商品の状態：{{ $product->condition ?? '—' }}</p>
        </div>
      </section>

      <section class="product-comments">
        <h3 class="comments-heading">
          コメント（<span id="comments-total">{{ $commentsCount }}</span>件）
        </h3>

        <ul id="comment-list" class="comment-list">
          @forelse (($product->comments ?? collect()) as $comment)
            <li class="comment-item" data-comment-id="{{ $comment->id }}">
              <div class="comment-user">
                @php
                  $profile = $comment->user->profile ?? null;
                  $icon = $profile && $profile->icon_image_path
                    ? asset('storage/' . $profile->icon_image_path)
                    : asset('img/sample.jpg');
                @endphp
                <img class="comment-user__icon"
                     src="{{ $icon }}"
                     alt="{{ $comment->user->name ?? 'ユーザー' }}のアイコン">
                <div class="comment-user__meta">
                  <span class="comment-user__name">{{ $comment->user->name ?? 'ユーザー' }}</span>
                  <time class="comment-created_at" datetime="{{ $comment->created_at }}">
                    {{ optional($comment->created_at)->format('Y/m/d H:i') }}
                  </time>
                </div>
              </div>
              <p class="comment-body">{{ $comment->body }}</p>
            </li>
          @empty
            <li class="comment-empty">まだコメントはありません。</li>
          @endforelse
        </ul>

        @php $canPost = auth()->check(); @endphp
        <form id="comment-form"
              class="comment-form"
              action="{{ $canPost ? route('comments.store', ['item_id' => $product->id]) : route('login') }}"
              method="{{ $canPost ? 'POST' : 'GET' }}"
              novalidate>
          @if ($canPost)
            @csrf
          @endif

          <label for="comment-body" class="comment-label">商品のコメント</label>
          <textarea
            id="comment-body"
            name="body"
            maxlength="255"
            required
          >{{ old('body') }}</textarea>

          @if ($canPost)
            @error('body')
              <p class="form-error" role="alert">{{ $message }}</p>
            @enderror
          @endif

          <button type="submit" class="comment-submit">
            コメントを送信する
          </button>
        </form>
      </section>
    </div>
  </article>
</main>
@endsection
