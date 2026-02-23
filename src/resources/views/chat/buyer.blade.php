<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('css/chat-buyer.css') }}">
  <title>取引チャット（購入者）</title>
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a href="{{ route('item') }}" class="header__logo">
      <img src="{{ asset('img/logo.svg') }}" alt="COACHTECHロゴ">
    </a>
  </div>
</header>

<div class="trade-chat">
  {{-- 左サイド（その他の取引） --}}
  <aside class="trade-chat__side">
    <div class="trade-chat__side-head">
      <p class="trade-chat__side-title">その他の取引</p>
    </div>

    <div class="trade-chat__side-list">
      @forelse($otherTransactions ?? [] as $t)
        <a class="trade-chat__side-product" href="{{ $t->chat_url ?? route('chat.buyer', ['transaction' => $t->id]) }}">
          <span>{{ $t->product_name ?? '商品名' }}</span>

          @if(($t->unread_messages_count ?? 0) > 0)
            <span class="trade-chat__badge">{{ $t->unread_messages_count }}</span>
          @endif
        </a>
      @empty
        <p class="trade-chat__side-empty">取引はありません</p>
      @endforelse
    </div>
  </aside>

  {{-- メイン --}}
  <main class="trade-chat__main">
    {{-- ヘッダー --}}
    <header class="trade-chat__header">
      <div class="trade-chat__header-left">
        <div class="trade-chat__avatar trade-chat__avatar--md"></div>
        <h1 class="trade-chat__title">
          「{{ $partnerUser->name ?? 'ユーザー名' }}」さんとの取引画面
        </h1>
      </div>

      <div class="trade-chat__header-right">
        {{-- 購入者用の取引完了 --}}
        <form action="{{ route('chat.complete', ['transaction' => $transaction->id ?? 0]) }}" method="POST">
          @csrf
          <button type="submit" class="trade-chat__complete-btn">取引を完了する</button>
        </form>
      </div>
    </header>

    {{-- 商品情報 --}}
    <section class="trade-chat__product">
      <div class="trade-chat__product-image">
        @if(!empty($product) && !empty($product->image_url))
          <img src="{{ $product->image_url }}" alt="商品画像">
        @else
          <div class="trade-chat__product-image-placeholder">商品画像</div>
        @endif
      </div>

      <div class="trade-chat__product-info">
        <p class="trade-chat__product-name">{{ $product->name ?? '商品名' }}</p>
        <p class="trade-chat__product-price">{{ $product->price ?? '商品価格' }}</p>
      </div>
    </section>

    {{-- チャット本文 --}}
    <section class="trade-chat__messages" aria-label="チャットメッセージ">
      @forelse($messages ?? [] as $message)
        @php
          $meId = $myId ?? auth()->id();
          $isMe = !empty($message->user_id) && !empty($meId) && ((int)$message->user_id === (int)$meId);
        @endphp

        <div class="trade-chat__message-row {{ $isMe ? 'is-mine' : 'is-theirs' }}">
          {{-- 名前＋アイコン（自分/相手で順番を分ける） --}}
          <div class="trade-chat__message-head {{ $isMe ? 'is-mine' : 'is-theirs' }}">
            @if($isMe)
              <p class="trade-chat__message-name">{{ auth()->user()->name ?? 'ユーザー名' }}</p>
              <div class="trade-chat__avatar trade-chat__avatar--sm"></div>
            @else
              <div class="trade-chat__avatar trade-chat__avatar--sm"></div>
              <p class="trade-chat__message-name">{{ $partnerUser->name ?? 'ユーザー名' }}</p>
            @endif
          </div>

          {{-- 吹き出し＋操作リンクをまとめる（CSS設計に合わせる） --}}
          <div class="trade-chat__message-content {{ $isMe ? 'is-mine' : 'is-theirs' }}">
            <div class="trade-chat__bubble">
              @if(!empty($message->body))
                <p class="trade-chat__message-text">{!! nl2br(e($message->body)) !!}</p>
              @endif

              @if(!empty($message->image_url))
                <div class="trade-chat__message-image">
                  <img src="{{ $message->image_url }}" alt="送信画像">
                </div>
              @endif
            </div>

            @if($isMe && !empty($message->id))
              <div class="trade-chat__message-actions">
                <a
                  href="{{ route('chat.message.edit', ['message' => $message->id]) }}"
                  class="trade-chat__action-link"
                >
                  編集
                </a>

                <form
                  action="{{ route('chat.message.destroy', ['message' => $message->id]) }}"
                  method="POST"
                  onsubmit="return confirm('このメッセージを削除しますか？')"
                >
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="trade-chat__action-link trade-chat__action-link--danger">
                    削除
                  </button>
                </form>
              </div>
            @endif
          </div>
        </div>
      @empty
        <div class="trade-chat__message-empty">
          <p>まだメッセージはありません。</p>
        </div>
      @endforelse
    </section>

    {{-- 入力エリア --}}
    <footer class="trade-chat__composer">
      {{-- バリデーションエラー表示（要件対応） --}}
      @if($errors->any())
        <div class="trade-chat__errors">
          @foreach($errors->all() as $error)
            <p class="trade-chat__error">{{ $error }}</p>
          @endforeach
        </div>
      @endif

      @if(session('status'))
        <div class="trade-chat__status">
          {{ session('status') }}
        </div>
      @endif

      <form action="{{ route('chat.message.store', ['transaction' => $transaction->id ?? 0]) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <input
          class="trade-chat__input"
          type="text"
          name="body"
          value="{{ old('body', $draftBody ?? '') }}"
          placeholder="取引メッセージを記入してください"
          autocomplete="off"
          maxlength="400"
        >

        <label class="trade-chat__image-add">
          <input type="file" name="image" accept=".png,.jpeg,image/png,image/jpeg">
          <span>画像を追加</span>
        </label>

        <button type="submit" class="trade-chat__modal-submit" aria-label="送信">
          <img src="{{ asset('img/inputbuttun.png') }}" alt="送信">
        </button>
      </form>
    </footer>

    {{-- 取引完了後の評価モーダル（購入者） --}}
    @if(!empty($showRatingModal) && $showRatingModal === true)
      <div class="trade-chat__modal-overlay" role="dialog" aria-modal="true" aria-label="取引完了">
        <div class="trade-chat__modal">
          <p class="trade-chat__modal-title">取引が完了しました。</p>
          <p class="trade-chat__modal-text">今回の取引相手はどうでしたか？</p>

          <form action="{{ route('chat.rate', ['transaction' => $transaction->id ?? 0]) }}" method="POST">
            @csrf

            <div class="trade-chat__rating" role="radiogroup" aria-label="評価">
              @for($i = 5; $i >= 1; $i--)
                <input
                  type="radio"
                  id="buyer-rating-{{ $i }}"
                  name="rating"
                  value="{{ $i }}"
                  class="trade-chat__rating-input"
                  {{ old('rating') == $i ? 'checked' : '' }}
                  required
                >
                <label for="buyer-rating-{{ $i }}" class="trade-chat__rating-star" aria-label="{{ $i }}点">★</label>
              @endfor
            </div>

            <div class="trade-chat__modal-actions">
              <button type="submit" class="trade-chat__modal-submit-btn">送信する</button>
            </div>
          </form>
        </div>
      </div>
    @endif
  </main>
</div>

</body>
</html>