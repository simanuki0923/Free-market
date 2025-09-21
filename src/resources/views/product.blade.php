@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/product.css') }}">
@endsection

@section('content')
<main class="product-detail__main container">

  {{-- 商品本体 --}}
  @php
    $src = $product->image_url
      ? (\Illuminate\Support\Str::startsWith($product->image_url, ['http://','https://'])
          ? $product->image_url
          : asset('storage/'.$product->image_url))
      : asset('img/no-image.png');   // public/img/no-image.png を用意

    $favoritesCount = $product->favorites_count ?? ($product->favorites->count() ?? 0);
    $commentsCount  = $product->comments_count  ?? ($product->comments->count()  ?? 0);
    $isFavorited    = $isFavorited ?? false;
  @endphp

  <article class="product-detail__container">
    {{-- 左：画像 --}}
    <figure class="product-detail__image">
      <img src="{{ $src }}" alt="{{ $product->name ?? '商品画像' }}">
    </figure>

    {{-- ★右：商品名〜コメント送信ボタンをまとめて青枠で囲う --}}
    <div class="product-pane">
      {{-- 右：テキスト情報 --}}
      <section class="product-detail__info">
        <h2 class="product-title">{{ $product->name ?? '商品名がありません' }}</h2>
        <p class="brand">{{ $product->brand ?? 'ブランド情報がありません' }}</p>

        <p class="price">
          @if (!is_null($product->price))
            ¥{{ number_format($product->price) }} <small>（税込）</small>
          @else
            価格が設定されていません
          @endif
        </p>

        {{-- いいね数コメント数（PNGアイコン下に数値） --}}
<aside class="action-buttons">
  <span class="favorite-button {{ $isFavorited ? 'favorited' : '' }}" aria-label="お気に入り数">
    <img class="iconstar" src="{{ asset('img/star.png') }}" alt="お気に入り" aria-hidden="true">
    <span class="action-count" id="favorite-count">{{ $favoritesCount }}</span>
  </span>
  <span class="comment-counter" aria-label="コメント数">
    <img class="iconcomment" src="{{ asset('img/comment.png') }}" alt="コメント" aria-hidden="true">
    <span class="action-count" id="comment-count">{{ $commentsCount }}</span>
  </span>
</aside>


        {{-- 購入導線（未実装ガード） --}}
        @php $purchaseReady = Route::has('purchase'); @endphp
        @if ($purchaseReady)
          <a class="purchase-button" href="{{ route('purchase', ['product' => $product->id]) }}">
            購入
          </a>
        @else
          <button class="purchase-button purchase-button--disabled" type="button" disabled title="現在準備中です">
            購入（準備中）
          </button>
        @endif

        {{-- 商品説明 / 商品情報 --}}
        <div class="product-info-item">
          <strong>商品説明</strong>
          <p class="description">{{ $product->description ?? '説明がありません' }}</p>

          <strong>商品の情報</strong>
          @php
            // 複数カテゴリ(多対多: categories)優先、なければ単一(category)にフォールバック
            $categoryNames = collect();
            if (method_exists($product, 'categories') && $product->relationLoaded('categories') && $product->categories->count()) {
                $categoryNames = $product->categories->pluck('name');
            } elseif (!empty($product->category) && !empty($product->category->name)) {
                $categoryNames = collect([$product->category->name]);
            }
          @endphp
          <p class="category">
            カテゴリー
            @if ($categoryNames->count())
              {{ $categoryNames->join(' / ') }}
            @else
              カテゴリが設定されていません
            @endif
          </p>
          <p class="condition">商品の状態　{{ $product->condition ?? '状態情報がありません' }}</p>
        </div>
      </section>

      {{-- =========================
           コメント（表示・入力・送信ボタン）
           ※ 常にフォーム表示
         ========================= --}}
      @php
        $commentsCount = $product->comments_count ?? ($product->comments->count() ?? 0);
      @endphp

      <section class="product-comments">
        <h3 class="comments-heading">
          コメント（<span id="comments-total">{{ $commentsCount }}</span>件）
        </h3>

        {{-- コメント一覧 --}}
        <ul id="comment-list" class="comment-list">
          @forelse (($product->comments ?? collect()) as $comment)
            <li class="comment-item" data-comment-id="{{ $comment->id }}">
              <div class="comment-user">
                @php
                  $profile = $comment->user->profile ?? null;
                  $icon = $profile && $profile->icon_image_path
                      ? asset('storage/'.$profile->icon_image_path)
                      : asset('img/sample.jpg');  // public/img/sample.jpg を用意
                @endphp
                <img class="comment-user__icon" src="{{ $icon }}" alt="{{ $comment->user->name ?? 'ユーザー' }}のアイコン">
                <div class="comment-user__meta">
                  <span class="comment-user__name">{{ $comment->user->name ?? 'ユーザー' }}</span>
                  <time class="comment-created_at" datetime="{{ $comment->created_at }}">
                    {{ $comment->created_at?->format('Y/m/d H:i') }}
                  </time>
                </div>
              </div>
              <p class="comment-body">{{ $comment->body }}</p>
            </li>
          @empty
            <li class="comment-empty">まだコメントはありません。</li>
          @endforelse
        </ul>

        {{-- コメント送信フォーム（常に表示） --}}
        <form id="comment-form"
              class="comment-form"
              action="{{ url('/product/'.$product->id.'/comments') }}"
              method="POST"
              novalidate>
          @csrf

          <label for="comment" class="comment-label">商品のコメント</label>
          <textarea
            id="comment"
            name="comment"
            maxlength="255"
            required
            placeholder="商品の状態や気になる点などをコメントしましょう（255文字まで）"
          >{{ old('comment') }}</textarea>

          @error('comment')
            <p class="form-error" role="alert">{{ $message }}</p>
          @enderror

          <button type="submit" class="comment-submit">コメントを送信する</button>
        </form>
      </section>
    </div> {{-- /.product-pane --}}
  </article>
</main>
@endsection
