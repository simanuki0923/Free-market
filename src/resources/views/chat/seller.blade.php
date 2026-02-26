<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/chat-seller.css') }}">
    <title>COACHTECH</title>
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
    <aside class="trade-chat__side">
        <div class="trade-chat__side-head">
            <h2 class="trade-chat__side-title">その他の取引</h2>
        </div>

        <div class="trade-chat__side-list">
            @forelse($otherTransactions ?? [] as $t)
                <a
                    class="trade-chat__side-product"
                    href="{{ $t->chat_url ?? route('chat.seller', ['transaction' => $t->id]) }}"
                >
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
                    $isMe = !empty($message->user_id) && !empty($meId) && ((int) $message->user_id === (int) $meId);
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
                        <div class="trade-chat__message-view" data-message-view="{{ $message->id }}">
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
                                    <button
                                        type="button"
                                        class="trade-chat__action-link trade-chat__action-btn"
                                        data-edit-open="{{ $message->id }}"
                                    >
                                        編集
                                    </button>

                                    <form
                                        action="{{ route('chat.message.destroy', ['message' => $message->id]) }}"
                                        method="POST"
                                        onsubmit="return confirm('このメッセージを削除しますか？');"
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

                        @if($isMe && !empty($message->id))
                            <div class="trade-chat__message-edit" data-message-edit="{{ $message->id }}" hidden>
                                <form
                                    action="{{ route('chat.message.update', ['message' => $message->id]) }}"
                                    method="POST"
                                    class="trade-chat__message-edit-form"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <textarea
                                        name="body"
                                        class="trade-chat__message-edit-textarea"
                                        maxlength="400"
                                        rows="3"
                                        required
                                    >{{ old('body', $message->body ?? '') }}</textarea>

                                    <div class="trade-chat__message-edit-actions">
                                        <button
                                            type="button"
                                            class="trade-chat__action-link trade-chat__action-btn"
                                            data-edit-cancel="{{ $message->id }}"
                                        >
                                            キャンセル
                                        </button>

                                        <button type="submit" class="trade-chat__message-save-btn">
                                            保存
                                        </button>
                                    </div>
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

        <footer class="trade-chat__composer">
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

            <form
                action="{{ route('chat.message.store', ['transaction' => $transaction->id ?? 0]) }}"
                method="POST"
                enctype="multipart/form-data"
            >
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
                                    id="seller-rating-{{ $i }}"
                                    name="rating"
                                    value="{{ $i }}"
                                    class="trade-chat__rating-input"
                                    {{ old('rating') == $i ? 'checked' : '' }}
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
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function closeAllEditors() {
            document.querySelectorAll('[data-message-edit]').forEach(function (el) {
                el.hidden = true;
            });

            document.querySelectorAll('[data-message-view]').forEach(function (el) {
                el.hidden = false;
            });
        }

        document.querySelectorAll('[data-edit-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                const id = this.dataset.editOpen;

                closeAllEditors();

                const view = document.querySelector('[data-message-view="' + id + '"]');
                const edit = document.querySelector('[data-message-edit="' + id + '"]');

                if (view) {
                    view.hidden = true;
                }

                if (edit) {
                    edit.hidden = false;

                    const textarea = edit.querySelector('textarea[name="body"]');
                    if (textarea) {
                        textarea.focus();
                        const len = textarea.value.length;
                        textarea.setSelectionRange(len, len);
                    }
                }
            });
        });

        document.querySelectorAll('[data-edit-cancel]').forEach(function (button) {
            button.addEventListener('click', function () {
                const id = this.dataset.editCancel;
                const view = document.querySelector('[data-message-view="' + id + '"]');
                const edit = document.querySelector('[data-message-edit="' + id + '"]');

                if (edit) {
                    edit.hidden = true;
                }

                if (view) {
                    view.hidden = false;
                }
            });
        });
    });
</script>

</body>
</html>