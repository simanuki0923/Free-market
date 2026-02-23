@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/chat-seller.css') }}">
@endsection

@section('content')
<div class="trade-chat">
  <aside class="trade-chat__side">
    <div class="trade-chat__side-head">
      <h2 class="trade-chat__side-title">その他の取引</h2>
    </div>

    <div class="trade-chat__side-list">
      @forelse($otherTransactions ?? [] as $t)
        <a class="trade-chat__side-product" href="{{ $t->chat_url ?? route('chat.seller', ['transaction' => $t->id]) }}">
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

  <main class="trade-chat__main">
    <header class="trade-chat__header">
      <div class="trade-chat__header-left">
        <div class="trade-chat__avatar trade-chat__avatar--md"></div>
        <h1 class="trade-chat__title">
          「{{ $partnerUser->name ?? 'ユーザー名' }}」さんとの取引画面
        </h1>
      </div>
      <div class="trade-chat__header-right">
        {{-- seller側は購入者が完了処理を行う --}}
      </div>
    </header>

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

    <section class="trade-chat__messages" aria-label="チャットメッセージ">
      @forelse($messages ?? [] as $message)
        @php
          $meId = $myId ?? auth()->id();
          $isMe = !empty($message->user_id) && !empty($meId) && ((int)$message->user_id === (int)$meId);
        @endphp

        <div class="trade-chat__message-row {{ $isMe ? 'is-mine' : 'is-theirs' }}">
          <div class="trade-chat__message-head {{ $isMe ? 'is-mine' : 'is-theirs' }}">
            @if($isMe)
              <p class="trade-chat__message-name">{{ auth()->user()->name ?? 'ユーザー名' }}</p>
              <div class="trade-chat__avatar trade-chat__avatar--sm"></div>
            @else
              <div class="trade-chat__avatar trade-chat__avatar--sm"></div>
              <p class="trade-chat__message-name">{{ $partnerUser->name ?? 'ユーザー名' }}</p>
            @endif
          </div>

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
                <a href="{{ route('chat.message.edit', ['message' => $message->id]) }}" class="trade-chat__action-link">
                  編集
                </a>
                <form action="{{ route('chat.message.destroy', ['message' => $message->id]) }}"
                      method="POST"
                      onsubmit="return confirm('このメッセージを削除しますか？');">
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
        <p class="trade-chat__empty">まだメッセージはありません。</p>
      @endforelse
    </section>

    @if ($errors->any())
      <div class="trade-chat__errors">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if (session('status'))
      <p class="trade-chat__status">{{ session('status') }}</p>
    @endif

    <section class="trade-chat__composer">
      <form action="{{ route('chat.message.store', ['transaction' => $transaction->id]) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="trade-chat__composer-row">
          <textarea
            name="body"
            class="trade-chat__textarea"
            rows="3"
            maxlength="400"
            placeholder="取引メッセージを記入してください"
          >{{ old('body', $draftBody ?? '') }}</textarea>
        </div>

        <div class="trade-chat__composer-actions">
          <label class="trade-chat__file-label">
            画像を追加
            <input type="file" name="image" accept=".jpg,.jpeg,.png,image/jpeg,image/png" hidden>
          </label>

          <div class="trade-chat__composer-buttons">
            <button type="submit" class="trade-chat__send-btn" aria-label="送信">送信</button>
          </div>
        </div>
      </form>

      <form action="{{ route('chat.draft.save', ['transaction' => $transaction->id]) }}" method="POST" class="trade-chat__draft-form">
        @csrf
        <input type="hidden" name="body" value="{{ old('body', $draftBody ?? '') }}">
        <button type="submit" class="trade-chat__draft-btn">下書き保存</button>
      </form>
    </section>
  </main>
</div>

@if(!empty($showRatingModal))
  <div class="trade-chat__modal-backdrop" role="dialog" aria-modal="true" aria-label="取引評価">
    <div class="trade-chat__modal">
      <h2 class="trade-chat__modal-title">購入者が取引完了しました。</h2>
      <p class="trade-chat__modal-subtitle">今回の取引相手を評価してください。</p>

      <form action="{{ route('chat.rate', ['transaction' => $transaction->id]) }}" method="POST">
        @csrf
        <div class="trade-chat__rating">
          @for($i = 1; $i <= 5; $i++)
            <input
              type="radio"
              id="seller-rating-{{ $i }}"
              name="rating"
              value="{{ $i }}"
              {{ (int) old('rating', 5) === $i ? 'checked' : '' }}
              required
            >
            <label for="seller-rating-{{ $i }}" class="trade-chat__rating-star" aria-label="{{ $i }}点">★</label>
          @endfor
        </div>

        <div class="trade-chat__modal-actions">
          <button type="submit" class="trade-chat__modal-submit-btn">送信する</button>
        </div>
      </form>
    </div>
  </div>
@endif
@endsection