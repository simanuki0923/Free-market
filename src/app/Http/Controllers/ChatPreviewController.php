<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRatingStoreRequest;
use App\Mail\TransactionCompletedMail;
use App\Models\ChatMessage;
use App\Models\ChatMessageRead;
use App\Models\Transaction;
use App\Models\TransactionRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ChatPreviewController extends Controller
{
    /**
     * 購入者チャット画面
     */
    public function buyer(Request $request, Transaction $transaction)
    {
        return $this->showChatScreen($request, $transaction, 'buyer');
    }

    /**
     * 出品者チャット画面
     */
    public function seller(Request $request, Transaction $transaction)
    {
        return $this->showChatScreen($request, $transaction, 'seller');
    }

    /**
     * 共通チャット画面表示
     */
    private function showChatScreen(Request $request, Transaction $transaction, string $screenRole)
    {
        $user = $request->user();
        $this->authorizeParticipant($transaction);

        $transaction->loadMissing([
            'product',
            'buyer',
            'seller',
            'messages.user',
            'ratings',
        ]);

        // 相手メッセージを既読化
        $this->markMessagesAsRead($transaction, (int) $user->id);

        $partnerUser = $screenRole === 'buyer'
            ? $transaction->seller
            : $transaction->buyer;

        // サイドバー用の他取引一覧
        $otherTransactions = Transaction::query()
            ->with(['product'])
            ->withUnreadCountFor((int) $user->id)
            ->where(function ($query) use ($user) {
                $query->where('buyer_id', $user->id)
                    ->orWhere('seller_id', $user->id);
            })
            ->whereKeyNot($transaction->id)
            ->orderByDesc('updated_at')
            ->get()
            ->filter(function (Transaction $targetTransaction) use ($user) {
                if ($targetTransaction->status === 'completed') {
                    return false;
                }

                $isBuyer = (int) $targetTransaction->buyer_id === (int) $user->id;
                $isSeller = (int) $targetTransaction->seller_id === (int) $user->id;

                // 購入者側：buyer_completed は取引中から除外
                if ($isBuyer && $targetTransaction->status === 'buyer_completed') {
                    return false;
                }

                // 出品者側：buyer_completed は評価導線として残す
                if (
                    $isSeller
                    && in_array($targetTransaction->status, ['ongoing', 'trading', 'buyer_completed'], true)
                ) {
                    return true;
                }

                return in_array($targetTransaction->status, ['ongoing', 'trading'], true);
            })
            ->map(function (Transaction $targetTransaction) use ($user) {
                $targetTransaction->product_name = $targetTransaction->product?->name ?? '商品名';
                $targetTransaction->chat_url = ((int) $targetTransaction->seller_id === (int) $user->id)
                    ? route('chat.seller', ['transaction' => $targetTransaction->id])
                    : route('chat.buyer', ['transaction' => $targetTransaction->id]);

                return $targetTransaction;
            })
            ->values();

        // Blade互換 product
        $product = (object) [
            'name'      => $transaction->product?->name ?? '商品名',
            'price'     => $transaction->product?->price ?? '商品価格',
            'image_url' => $transaction->product_image_url ?? null,
        ];

        // Blade互換 messages
        $messages = $transaction->messages->map(function (ChatMessage $message) {
            return (object) [
                'id'         => $message->id,
                'user_id'    => $message->user_id,
                'body'       => $message->body,
                'image_url'  => $this->resolveChatMessageImageUrl($message),
                'created_at' => $message->created_at,
                'edited_at'  => $message->edited_at ?? null,
            ];
        });

        // 評価モーダル表示判定
        $alreadyRatedByMe = $transaction->ratings()
            ->where('rater_user_id', $user->id)
            ->exists();

        $showRatingModal = false;

        if ($screenRole === 'buyer') {
            $showRatingModal = (bool) $request->boolean('rate', false);
        } else {
            $showRatingModal =
                ($transaction->status === 'buyer_completed' || $transaction->buyer_completed_at !== null)
                && !$alreadyRatedByMe;
        }

        $viewName = $screenRole === 'buyer' ? 'chat.buyer' : 'chat.seller';
        $draftBody = session($this->draftSessionKey((int) $transaction->id), '');

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

    /**
     * チャットメッセージ送信
     * - 本文 or 画像のどちらか必須
     * - 本文最大400文字
     * - 画像は jpeg/png
     */
    public function storeMessage(Request $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($transaction);

        $validated = $request->validate([
            'body'  => ['nullable', 'string', 'max:400', 'required_without:image'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'required_without:body'],
        ], [
            'body.required_without'  => '本文を入力してください。',
            'body.max'               => '本文は400文字以内で入力してください。',
            'image.required_without' => '画像を選択してください。',
            'image.mimes'            => '画像はjpegまたはpng形式でアップロードしてください。',
        ]);

        $body = trim((string) ($validated['body'] ?? ''));
        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('chat-images', 'public');
        }

        // 空白だけ送信対策（最終防御）
        if ($body === '' && $imagePath === null) {
            return back()
                ->withErrors(['body' => '本文または画像を入力してください。'])
                ->withInput();
        }

        ChatMessage::create([
            'transaction_id' => (int) $transaction->id,
            'user_id'        => (int) $user->id,
            'body'           => $body !== '' ? $body : null,
            'image_path'     => $imagePath, // DB定義に合わせる
        ]);

        // 下書きクリア
        $request->session()->forget($this->draftSessionKey((int) $transaction->id));

        // 一覧の並び順更新
        $transaction->update([
            'last_message_at' => now(),
        ]);

        return $this->redirectToChatByUserRole($transaction, (int) $user->id)
            ->with('status', 'メッセージを送信しました。');
    }

    /**
     * 本文下書き保存（本文のみ）
     */
    public function saveDraft(Request $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($transaction);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:400'],
        ], [
            'body.max' => '本文は400文字以内で入力してください。',
        ]);

        $draftBody = (string) ($validated['body'] ?? '');
        $request->session()->put($this->draftSessionKey((int) $transaction->id), $draftBody);

        return $this->redirectToChatByUserRole($transaction, (int) $user->id)
            ->with('status', '下書きを保存しました。');
    }

    /**
     * メッセージ編集画面
     */
    public function editMessage(Request $request, ChatMessage $message)
    {
        $transaction = $message->transaction()->firstOrFail();
        $this->authorizeParticipant($transaction);
        $this->authorizeMessageOwner($message, (int) $request->user()->id);

        $transaction->loadMissing(['buyer', 'seller', 'product']);
        $message->loadMissing('user');

        // 必要なら専用viewに変更（chat.message-edit など）
        return view('chat.message-edit', [
            'transaction' => $transaction,
            'message'     => $message,
            'myId'        => (int) $request->user()->id,
        ]);
    }

    /**
     * メッセージ更新
     * - 本文のみ更新（画像差し替え要件がない前提）
     */
    public function updateMessage(Request $request, ChatMessage $message): RedirectResponse
    {
        $transaction = $message->transaction()->firstOrFail();
        $this->authorizeParticipant($transaction);
        $this->authorizeMessageOwner($message, (int) $request->user()->id);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:400'],
        ], [
            'body.required' => '本文を入力してください。',
            'body.max'      => '本文は400文字以内で入力してください。',
        ]);

        $newBody = trim((string) $validated['body']);

        if ($newBody === '') {
            return back()
                ->withErrors(['body' => '本文を入力してください。'])
                ->withInput();
        }

        $message->update([
            'body'      => $newBody,
            'edited_at' => now(),
        ]);

        $transaction->update([
            'last_message_at' => now(),
        ]);

        return $this->redirectToChatByUserRole($transaction, (int) $request->user()->id)
            ->with('status', 'メッセージを更新しました。');
    }

    /**
     * メッセージ削除
     */
    public function destroyMessage(Request $request, ChatMessage $message): RedirectResponse
    {
        $transaction = $message->transaction()->firstOrFail();
        $this->authorizeParticipant($transaction);
        $this->authorizeMessageOwner($message, (int) $request->user()->id);

        // 画像があればストレージ削除（public配下のみ）
        $imagePath = $message->image_path ?? null;
        if (!empty($imagePath) && !str_starts_with($imagePath, 'http://') && !str_starts_with($imagePath, 'https://')) {
            try {
                Storage::disk('public')->delete(ltrim($imagePath, '/'));
            } catch (\Throwable $e) {
                Log::warning('チャット画像削除失敗', [
                    'chat_message_id' => $message->id,
                    'image_path'      => $imagePath,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        $message->delete();

        $transaction->update([
            'last_message_at' => now(),
        ]);

        return $this->redirectToChatByUserRole($transaction, (int) $request->user()->id)
            ->with('status', 'メッセージを削除しました。');
    }

    /**
     * 取引完了（購入者）
     * - buyer_completed_at をセット
     * - status = buyer_completed
     * - 出品者へメール通知
     * - 購入者側は ?rate=1 で評価モーダル表示
     */
    public function completeTransaction(Request $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($transaction);

        if ((int) $transaction->buyer_id !== (int) $user->id) {
            abort(403, '購入者のみ取引完了できます。');
        }

        // 初回のみ更新（多重送信防止）
        if ($transaction->buyer_completed_at === null) {
            $transaction->update([
                'buyer_completed_at' => now(),
                'status'             => 'buyer_completed',
            ]);

            $transaction->loadMissing(['seller', 'buyer', 'product']);

            if (!empty($transaction->seller?->email)) {
                try {
                    Mail::to($transaction->seller->email)
                        ->send(new TransactionCompletedMail($transaction));
                } catch (\Throwable $e) {
                    Log::error('取引完了メール送信失敗', [
                        'transaction_id' => $transaction->id,
                        'seller_id'      => $transaction->seller_id,
                        'seller_email'   => $transaction->seller?->email,
                        'error'          => $e->getMessage(),
                    ]);
                }
            }
        }

        // 購入者側の評価モーダル表示
        return redirect()->route('chat.buyer', [
            'transaction' => $transaction->id,
            'rate'        => 1,
        ]);
    }

    /**
     * 評価送信（購入者/出品者）
     * - 二重評価防止
     * - 両者評価完了で completed
     * - 要件に合わせて送信後は商品一覧へ
     */
    public function storeRating(TransactionRatingStoreRequest $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParticipant($transaction);

        $myId = (int) $user->id;
        $buyerId = (int) ($transaction->buyer_id ?? 0);
        $sellerId = (int) ($transaction->seller_id ?? 0);

        if (!in_array($myId, [$buyerId, $sellerId], true)) {
            abort(403, 'この取引を評価できません。');
        }

        $rating = (int) $request->input('rating');
        $rateeUserId = ($myId === $buyerId) ? $sellerId : $buyerId;

        if ($rateeUserId <= 0) {
            return back()
                ->withErrors(['rating' => '評価対象ユーザーを特定できませんでした。'])
                ->withInput();
        }

        DB::transaction(function () use ($transaction, $myId, $rateeUserId, $rating, $buyerId, $sellerId) {
            TransactionRating::updateOrCreate(
                [
                    'transaction_id' => (int) $transaction->id,
                    'rater_user_id'  => $myId,
                ],
                [
                    'ratee_user_id' => $rateeUserId,
                    'rating'        => $rating,
                    'rated_at'      => now(),
                ]
            );

            $ratedUserIds = TransactionRating::query()
                ->where('transaction_id', (int) $transaction->id)
                ->pluck('rater_user_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $bothRated = $ratedUserIds->contains($buyerId) && $ratedUserIds->contains($sellerId);

            if ($bothRated && $transaction->status !== 'completed') {
                $updatePayload = [
                    'status' => 'completed',
                ];

                if (empty($transaction->completed_at)) {
                    $updatePayload['completed_at'] = now();
                }

                $transaction->update($updatePayload);
            }
        });

        return redirect()->route('item')
            ->with('status', '評価を送信しました。');
    }

    /**
     * 相手メッセージを既読化
     */
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

    /**
     * 取引参加者チェック
     */
    private function authorizeParticipant(Transaction $transaction): void
    {
        $userId = auth()->id();

        if (!$userId) {
            abort(401);
        }

        if (
            (int) $transaction->seller_id !== (int) $userId
            && (int) $transaction->buyer_id !== (int) $userId
        ) {
            abort(403, 'この取引にアクセスできません。');
        }
    }

    /**
     * メッセージ所有者チェック（編集/削除は送信者のみ）
     */
    private function authorizeMessageOwner(ChatMessage $message, int $userId): void
    {
        if ((int) $message->user_id !== $userId) {
            abort(403, 'このメッセージを操作できません。');
        }
    }

    /**
     * 役割に応じてチャット画面へ戻す
     */
    private function redirectToChatByUserRole(Transaction $transaction, int $userId): RedirectResponse
    {
        $routeName = ((int) $transaction->buyer_id === $userId)
            ? 'chat.buyer'
            : 'chat.seller';

        return redirect()->route($routeName, [
            'transaction' => $transaction->id,
        ]);
    }

    /**
     * 下書きセッションキー
     */
    private function draftSessionKey(int $transactionId): string
    {
        return "chat_draft_transaction_{$transactionId}";
    }

    /**
     * chat_messages の画像URL解決
     * - DBは image_path 前提
     * - 旧実装の image_url カラム/アクセサにも互換対応
     */
    private function resolveChatMessageImageUrl(ChatMessage $message): ?string
    {
        $rawPath = $message->image_url ?? $message->image_path ?? null;

        if (empty($rawPath)) {
            return null;
        }

        if (str_starts_with($rawPath, 'http://') || str_starts_with($rawPath, 'https://')) {
            return $rawPath;
        }

        return Storage::disk('public')->url(ltrim($rawPath, '/'));
    }
}