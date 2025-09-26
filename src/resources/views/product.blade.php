{{-- resources/views/product.blade.php --}}
@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/product.css') }}">
@endsection

@section('content')
<main class="product-detail__main container">
  @php
    /*
     * 画像URLの決定（優先順）
     * 1) products.image_url
     * 2) sells.image_path（方式Aで product->sell が1:1で紐づく）
     * 3) /img/no-image.png
     * その上で外部URL/ローカルstorageを判定
     */
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

    // 件数（withCount を最優先、無ければリレーションでフォールバック）
    $favoritesCount = $product->favorites_count ?? ($product->favoredByUsers()->count() ?? 0);
    $commentsCount  = $commentsCount ?? ($product->comments_count ?? ($product->comments()->count() ?? 0));

    // お気に入り状態（コントローラから渡されていればそれを使用）
    $isFavorited = $isFavorited ?? false;
  @endphp

  <article class="product-detail__container">
    {{-- 左：画像（product.css の .product-detail__image img が効く） --}}
    <figure class="product-detail__image">
      <img src="{{ $src }}" alt="{{ $product->name ?? '商品画像' }}">
    </figure>

    {{-- 右ペイン --}}
    <div class="product-pane">
      {{-- 基本情報 --}}
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

        {{-- いいね・コメント（PNGアイコンの下に数値） --}}
        <aside class="action-buttons">
          {{-- お気に入りトグル --}}
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

          {{-- コメント件数（表示のみ） --}}
          <span class="comment-counter" aria-label="コメント数">
            <img class="iconcomment" src="{{ asset('img/comment.png') }}" alt="コメント" aria-hidden="true">
            <span class="action-count" id="comment-count">{{ $commentsCount }}</span>
          </span>
        </aside>

        {{-- 購入ボタン：/purchase/{item_id} --}}
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
            // 複数カテゴリ(多対多: categories)優先、無ければ単一(category)へフォールバック
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

      {{-- =========================
           コメント（一覧・入力・送信）
         ========================= --}}
      <section class="product-comments">
        <h3 class="comments-heading">
          コメント（<span id="comments-total">{{ $commentsCount }}</span>件）
        </h3>

        {{-- 一覧 --}}
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

        {{-- 送信フォーム（ログイン時は保存、未ログイン時はログイン画面へ遷移） --}}
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
            placeholder="商品の状態や気になる点などをコメントしましょう（255文字まで）"
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
    </div>{{-- /.product-pane --}}
  </article>
</main>
@endsection
