<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatMessageStoreRequest;
use App\Http\Requests\ChatMessageUpdateRequest;
use App\Http\Requests\TransactionRatingStoreRequest;
use App\Mail\TransactionCompletedMail;
use App\Models\ChatMessage;
use App\Models\ChatMessageRead;
use App\Models\Transaction;
use App\Models\TransactionRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ChatPreviewController extends Controller
{
    /**
     * 購入者画面
     */
    public function buyer(Request $request, Transaction $transaction): View
    {
        $this->authorizeParticipant($transaction);

        return $this->renderChatView($request, $transaction, 'chat.buyer', 'buyer');
    }

    /**
     * 出品者画面
     */
    public function seller(Request $request, Transaction $transaction): View
    {
        $this->authorizeParticipant($transaction);

        return $this->renderChatView($request, $transaction, 'chat.seller', 'seller');
    }

    /**
     * メッセージ投稿
     */
    public function storeMessage(ChatMessageStoreRequest $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($transaction);

        $body = trim((string) $request->input('body', ''));
        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('chat-images', 'public');
        }

        // 本文必須要件。画像のみ投稿を許可したい場合はここを調整。
        if ($body === '') {
            return back()
                ->withErrors(['body' => '本文を入力してください'])
                ->withInput();
        }

        DB::transaction(function () use ($transaction, $user, $body, $imagePath) {
            $message = ChatMessage::create([
                'transaction_id' => $transaction->id,
                'user_id'        => $user->id,
                'body'           => $body,
                'image_path'     => $imagePath,
            ]);

            // 送信者は既読扱い
            ChatMessageRead::updateOrCreate(
                [
                    'chat_message_id' => $message->id,
                    'user_id'         => $user->id,
                ],
                [
                    'read_at' => now(),
                ]
            );

            // 取引の最新更新時刻を更新（並び順用）
            $transaction->forceFill([
                'last_message_at' => now(),
            ])->save();
        });

        // 下書きクリア
        session()->forget($this->draftSessionKey($transaction->id));

        return redirect()->to($this->chatRouteByRole($transaction, $user->id))
            ->with('status', 'メッセージを送信しました');
    }

    /**
     * 編集画面（簡易）
     */
    public function editMessage(Request $request, ChatMessage $message): View
    {
        $message->load('transaction');
        $this->authorizeMessageOwner($message, (int) $request->user()->id);

        return view('chat.message-edit', [
            'message'     => $message,
            'transaction' => $message->transaction,
        ]);
    }

    /**
     * メッセージ更新
     */
    public function updateMessage(ChatMessageUpdateRequest $request, ChatMessage $message): RedirectResponse
    {
        $user = $request->user();
        $message->load('transaction');
        $this->authorizeMessageOwner($message, (int) $user->id);

        $body = trim((string) $request->input('body', ''));

        $imagePath = $message->image_path;
        if ($request->boolean('remove_image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = null;
        }

        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('chat-images', 'public');
        }

        $message->update([
            'body'       => $body,
            'image_path' => $imagePath,
            'edited_at'  => now(),
        ]);

        return redirect()->to($this->chatRouteByRole($message->transaction, (int) $user->id))
            ->with('status', 'メッセージを更新しました');
    }

    /**
     * メッセージ削除
     */
    public function destroyMessage(Request $request, ChatMessage $message): RedirectResponse
    {
        $user = $request->user();
        $message->load('transaction');
        $this->authorizeMessageOwner($message, (int) $user->id);

        $redirectUrl = $this->chatRouteByRole($message->transaction, (int) $user->id);

        DB::transaction(function () use ($message) {
            if ($message->image_path) {
                Storage::disk('public')->delete($message->image_path);
            }
            $message->delete();
        });

        return redirect()->to($redirectUrl)
            ->with('status', 'メッセージを削除しました');
    }

    /**
     * 本文下書き保存（本文のみ）
     */
    public function saveDraft(Request $request, Transaction $transaction): \Illuminate\Http\JsonResponse
    {
        $this->authorizeParticipant($transaction);

        $request->validate([
            'body' => ['nullable', 'string', 'max:400'],
        ]);

        session([
            $this->draftSessionKey($transaction->id) => (string) $request->input('body', ''),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * 取引完了（購入者が押す）
     * - buyer_completed_at をセット
     * - 出品者宛に自動通知メール送信（Mailhog / Mailtrap）
     * - seller はチャット画面表示時に評価モーダル対象になる
     */
    public function completeTransaction(Request $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($transaction);

        if ((int) $transaction->buyer_id !== (int) $user->id) {
            abort(403, '購入者のみ取引完了できます。');
        }

        // 二重送信防止（初回のみ更新＆通知）
        if ($transaction->buyer_completed_at === null) {
            $transaction->update([
                'buyer_completed_at' => now(),
                'status'             => 'buyer_completed',
            ]);

            // メール送信に必要な関連を読み込む
            $transaction->loadMissing(['seller', 'buyer', 'product']);

            // 出品者メールがある場合のみ送信
            if (!empty($transaction->seller?->email)) {
                try {
                    Mail::to($transaction->seller->email)
                        ->send(new TransactionCompletedMail($transaction));
                } catch (\Throwable $e) {
                    // ログに残して画面は継続（開発時の切り分け用）
                    Log::error('取引完了メール送信失敗', [
                        'transaction_id' => $transaction->id,
                        'seller_id'      => $transaction->seller_id,
                        'seller_email'   => $transaction->seller?->email,
                        'error'          => $e->getMessage(),
                    ]);
                }
            }
        }

        return redirect()
            ->route('chat.buyer', ['transaction' => $transaction->id, 'rate' => 1]);
    }

    /**
     * 評価送信（購入者/出品者）
     * - 送信後は商品一覧へ遷移（要件）
     */
    public function storeRating(TransactionRatingStoreRequest $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($transaction);

        // 自分が評価する対象者を決定
        $rateeUserId = ((int) $user->id === (int) $transaction->buyer_id)
            ? $transaction->seller_id
            : $transaction->buyer_id;

        TransactionRating::updateOrCreate(
            [
                'transaction_id' => $transaction->id,
                'rater_user_id'  => $user->id,
            ],
            [
                'ratee_user_id' => $rateeUserId,
                'rating'        => (int) $request->integer('rating'),
                'comment'       => $request->input('comment'),
                'rated_at'      => now(),
            ]
        );

        // 両者評価済みなら completed
        $ratingsCount = $transaction->ratings()->count();
        if ($ratingsCount >= 2 && $transaction->status !== 'completed') {
            $transaction->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        }

        return redirect()->route('item')
            ->with('status', '評価を送信しました');
    }

    /**
     * 共通描画処理
     */
    private function renderChatView(Request $request, Transaction $transaction, string $viewName, string $screenRole): View
    {
        $user = $request->user();

        $transaction->load([
            'seller.profile',
            'buyer.profile',
            'product',
            'messages.user',
            'messages.reads',
            'ratings',
        ]);

        // 相手ユーザー
        $partnerUser = ((int) $user->id === (int) $transaction->seller_id)
            ? $transaction->buyer
            : $transaction->seller;

        // 画面表示時に相手メッセージを既読にする
        $this->markMessagesAsRead($transaction, (int) $user->id);

        // サイドバー（その他の取引）
        $otherTransactions = Transaction::query()
            ->with(['product'])
            ->forParticipant((int) $user->id)
            ->where('id', '!=', $transaction->id)
            ->withUnreadCountFor((int) $user->id)
            ->orderByDesc(DB::raw('COALESCE(last_message_at, updated_at)'))
            ->get()
            ->map(function (Transaction $t) use ($user) {
                $t->product_name = $t->product?->name ?? '商品名';
                $t->chat_url = ((int) $t->seller_id === (int) $user->id)
                    ? route('chat.seller', $t)
                    : route('chat.buyer', $t);

                return $t;
            });

        // Bladeで使う product 互換オブジェクト
        $product = (object) [
            'name'      => $transaction->product?->name ?? '商品名',
            'price'     => $transaction->product?->price ?? '商品価格',
            'image_url' => $transaction->product_image_url,
        ];

        // Blade互換メッセージ整形
        $messages = $transaction->messages->map(function (ChatMessage $m) {
            return (object) [
                'id'        => $m->id,
                'user_id'   => $m->user_id,
                'body'      => $m->body,
                'image_url' => $m->image_url,
            ];
        });

        // 評価モーダル表示判定
        // buyer: complete押下後の ?rate=1
        // seller: buyer_completed_at が入っていて、まだ自分が評価していない場合に表示
        $alreadyRatedByMe = $transaction->ratings()
            ->where('rater_user_id', $user->id)
            ->exists();

        $showRatingModal = false;
        if ($screenRole === 'buyer') {
            $showRatingModal = (bool) $request->boolean('rate', false);
        } else {
            $showRatingModal = $transaction->buyer_completed_at !== null && !$alreadyRatedByMe;
        }

        $draftBody = session($this->draftSessionKey($transaction->id), '');

        return view($viewName, [
            'partnerUser'       => $partnerUser,
            'product'           => $product,
            'transaction'       => $transaction,
            'messages'          => $messages,
            'otherTransactions' => $otherTransactions,
            'showRatingModal'   => $showRatingModal,
            'myId'              => $user->id,
            'draftBody'         => $draftBody,
        ]);
    }

    private function markMessagesAsRead(Transaction $transaction, int $userId): void
    {
        $messageIds = $transaction->messages()
            ->where('user_id', '!=', $userId)
            ->pluck('id');

        foreach ($messageIds as $messageId) {
            ChatMessageRead::updateOrCreate(
                [
                    'chat_message_id' => $messageId,
                    'user_id'         => $userId,
                ],
                [
                    'read_at' => now(),
                ]
            );
        }
    }

    private function authorizeParticipant(Transaction $transaction): void
    {
        $userId = auth()->id();

        if (!$userId) {
            abort(401);
        }

        if ((int) $transaction->seller_id !== (int) $userId && (int) $transaction->buyer_id !== (int) $userId) {
            abort(403, 'この取引にアクセスできません。');
        }
    }

    private function authorizeMessageOwner(ChatMessage $message, int $userId): void
    {
        if ((int) $message->user_id !== (int) $userId) {
            abort(403, '自分のメッセージのみ編集/削除できます。');
        }

        $this->authorizeParticipant($message->transaction);
    }

    private function chatRouteByRole(Transaction $transaction, int $userId): string
    {
        if ((int) $transaction->seller_id === (int) $userId) {
            return route('chat.seller', ['transaction' => $transaction->id]);
        }

        return route('chat.buyer', ['transaction' => $transaction->id]);
    }

    private function draftSessionKey(int $transactionId): string
    {
        return "chat_draft.transaction.{$transactionId}";
    }
}