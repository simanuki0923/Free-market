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

        // 必要な関連を読み込み
        $transaction->loadMissing([
            'product',
            'buyer',
            'seller',
            'messages.user',
            'ratings',
        ]);

        // 既読化（相手メッセージを既読）
        $this->markMessagesAsRead($transaction, (int) $user->id);

        // 相手ユーザー
        $partnerUser = $screenRole === 'buyer'
            ? $transaction->seller
            : $transaction->buyer;

        // 他の取引一覧（サイドバー）
        // ※ buyer_completed を出品者側には残す（評価導線）
        $otherTransactions = Transaction::query()
            ->with(['product'])
            ->withUnreadCountFor((int) $user->id)
            ->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                    ->orWhere('seller_id', $user->id);
            })
            ->whereKeyNot($transaction->id)
            ->orderByDesc('updated_at')
            ->get()
            ->filter(function (Transaction $t) use ($user) {
                // completed は除外
                if ($t->status === 'completed') {
                    return false;
                }

                $isBuyer = (int) $t->buyer_id === (int) $user->id;
                $isSeller = (int) $t->seller_id === (int) $user->id;

                // 購入者側：buyer_completed は取引中から除外
                if ($isBuyer && $t->status === 'buyer_completed') {
                    return false;
                }

                // 出品者側：buyer_completed は評価導線として残す
                if ($isSeller && in_array($t->status, ['ongoing', 'trading', 'buyer_completed'], true)) {
                    return true;
                }

                return in_array($t->status, ['ongoing', 'trading'], true);
            })
            ->map(function (Transaction $t) use ($user) {
                $t->product_name = $t->product?->name ?? '商品名';
                $t->chat_url = ((int) $t->seller_id === (int) $user->id)
                    ? route('chat.seller', ['transaction' => $t->id])
                    : route('chat.buyer', ['transaction' => $t->id]);

                return $t;
            })
            ->values();

        // Blade互換 product
        $product = (object) [
            'name'      => $transaction->product?->name ?? '商品名',
            'price'     => $transaction->product?->price ?? '商品価格',
            'image_url' => $transaction->product_image_url ?? null,
        ];

        // Blade互換 messages
        $messages = $transaction->messages->map(function (ChatMessage $m) {
            return (object) [
                'id'         => $m->id,
                'user_id'    => $m->user_id,
                'body'       => $m->body,
                'image_url'  => $m->image_url ?? null,
                'created_at' => $m->created_at,
            ];
        });

        // --- 評価モーダル表示判定（重要） ---
        // buyer: complete押下後の ?rate=1 で表示
        // seller: 購入者完了後 + 未評価ならチャット表示時に自動表示
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
        $imageUrl = null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('chat-images', 'public');
            $imageUrl = Storage::disk('public')->url($path);
        }

        // 空白だけ送信対策（最終防御）
        if ($body === '' && $imageUrl === null) {
            return back()
                ->withErrors(['body' => '本文または画像を入力してください。'])
                ->withInput();
        }

        ChatMessage::create([
            'transaction_id' => (int) $transaction->id,
            'user_id'        => (int) $user->id,
            'body'           => $body !== '' ? $body : null,
            'image_url'      => $imageUrl,
        ]);

        // 下書きクリア
        $request->session()->forget($this->draftSessionKey((int) $transaction->id));

        // 一覧の並び順用
        $transaction->touch();

        // 送信後は自分の役割に応じたチャット画面へ戻す
        $routeName = ((int) $transaction->buyer_id === (int) $user->id)
            ? 'chat.buyer'
            : 'chat.seller';

        return redirect()
            ->route($routeName, ['transaction' => $transaction->id])
            ->with('status', 'メッセージを送信しました。');
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

        // 念のため当事者チェック（authorizeParticipant済みだが明示）
        if (!in_array($myId, [$buyerId, $sellerId], true)) {
            abort(403, 'この取引を評価できません。');
        }

        // 評価値
        $rating = (int) $request->input('rating');

        // 評価対象（相手）
        $rateeUserId = ($myId === $buyerId) ? $sellerId : $buyerId;

        if ($rateeUserId <= 0) {
            return back()
                ->withErrors(['rating' => '評価対象ユーザーを特定できませんでした。'])
                ->withInput();
        }

        DB::transaction(function () use ($transaction, $myId, $rateeUserId, $rating, $buyerId, $sellerId) {
            // 二重評価防止（updateOrCreate）
            // ※ ratee_user_id は NOT NULL のため必須
            TransactionRating::updateOrCreate(
                [
                    'transaction_id' => (int) $transaction->id,
                    'rater_user_id'  => $myId,
                ],
                [
                    'ratee_user_id'  => $rateeUserId,
                    'rating'         => $rating,
                    'rated_at'       => now(), // モデルにrated_atがあるので記録しておく
                ]
            );

            // 両者評価が揃ったら completed にする
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

                // 初回のみ completed_at を入れる（上書きしない）
                if (empty($transaction->completed_at)) {
                    $updatePayload['completed_at'] = now();
                }

                $transaction->update($updatePayload);
            }
        });

        // 要件：評価送信後は商品一覧画面へ
        return redirect()->route('item')
            ->with('status', '評価を送信しました。');
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

    private function draftSessionKey(int $transactionId): string
    {
        return "chat_draft_transaction_{$transactionId}";
    }
}