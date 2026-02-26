<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatMessageRead;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ChatScreenController extends Controller
{
    private const STATUS_ONGOING = 'ongoing';
    private const STATUS_TRADING = 'trading';
    private const STATUS_BUYER_COMPLETED = 'buyer_completed';
    private const STATUS_COMPLETED = 'completed';

    private const SCREEN_ROLE_BUYER = 'buyer';
    private const SCREEN_ROLE_SELLER = 'seller';

    public function buyer(Request $request, Transaction $transaction)
    {
        return $this->show($request, $transaction, self::SCREEN_ROLE_BUYER, 'chat.buyer');
    }

    public function seller(Request $request, Transaction $transaction)
    {
        return $this->show($request, $transaction, self::SCREEN_ROLE_SELLER, 'chat.seller');
    }

    private function show(Request $request, Transaction $transaction, string $screenRole, string $viewName)
    {
        $user = $request->user();

        $this->authorizeParticipant($transaction, (int) $user->id);
        $this->loadChatScreenRelations($transaction);
        $this->markMessagesAsRead($transaction, (int) $user->id);

        $partnerUser = $this->resolvePartnerUser($transaction, $screenRole);
        $otherTransactions = $this->buildOtherTransactions($transaction, (int) $user->id);
        $product = $this->buildProductViewData($transaction);
        $messages = $this->buildMessageViewData($transaction);
        $showRatingModal = $this->shouldShowRatingModal(
            $request,
            $transaction,
            $screenRole,
            (int) $user->id
        );

        $draftBody = session($this->draftSessionKey((int) $transaction->id), '');

        return view($viewName, [
            'partnerUser' => $partnerUser,
            'product' => $product,
            'transaction' => $transaction,
            'messages' => $messages,
            'otherTransactions' => $otherTransactions,
            'showRatingModal' => $showRatingModal,
            'myId' => $user->id,
            'draftBody' => $draftBody,
        ]);
    }

    private function authorizeParticipant(Transaction $transaction, int $userId): void
    {
        if (
            (int) $transaction->seller_id !== $userId
            && (int) $transaction->buyer_id !== $userId
        ) {
            abort(403, 'この取引にアクセスできません。');
        }
    }

    private function loadChatScreenRelations(Transaction $transaction): void
    {
        $transaction->loadMissing([
            'product',
            'buyer',
            'seller',
            'messages.user',
            'ratings',
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
                    'user_id' => $userId,
                ],
                [
                    'read_at' => now(),
                ]
            );
        }
    }

    private function resolvePartnerUser(Transaction $transaction, string $screenRole)
    {
        return $screenRole === self::SCREEN_ROLE_BUYER
            ? $transaction->seller
            : $transaction->buyer;
    }

    private function buildOtherTransactions(Transaction $currentTransaction, int $userId): Collection
    {
        return Transaction::query()
            ->with(['product'])
            ->withUnreadCountFor($userId)
            ->where(function ($query) use ($userId): void {
                $query->where('buyer_id', $userId)
                    ->orWhere('seller_id', $userId);
            })
            ->whereKeyNot($currentTransaction->id)
            ->orderByDesc('updated_at')
            ->get()
            ->filter(fn (Transaction $transaction): bool => $this->isVisibleOtherTransaction($transaction, $userId))
            ->map(fn (Transaction $transaction): Transaction => $this->mapOtherTransactionForView($transaction, $userId))
            ->values();
    }

    private function isVisibleOtherTransaction(Transaction $transaction, int $userId): bool
    {
        if ($transaction->status === self::STATUS_COMPLETED) {
            return false;
        }

        $isBuyer = (int) $transaction->buyer_id === $userId;
        $isSeller = (int) $transaction->seller_id === $userId;

        if ($isBuyer && $transaction->status === self::STATUS_BUYER_COMPLETED) {
            return false;
        }

        if ($isSeller) {
            return in_array(
                $transaction->status,
                [self::STATUS_ONGOING, self::STATUS_TRADING, self::STATUS_BUYER_COMPLETED],
                true
            );
        }

        return in_array($transaction->status, [self::STATUS_ONGOING, self::STATUS_TRADING], true);
    }

    private function mapOtherTransactionForView(Transaction $transaction, int $userId): Transaction
    {
        $transaction->product_name = $transaction->product?->name ?? '商品名';
        $transaction->chat_url = (int) $transaction->seller_id === $userId
            ? route('chat.seller', ['transaction' => $transaction->id])
            : route('chat.buyer', ['transaction' => $transaction->id]);

        return $transaction;
    }

    private function buildProductViewData(Transaction $transaction): object
    {
        return (object) [
            'name' => $transaction->product?->name ?? '商品名',
            'price' => $transaction->product?->price ?? '商品価格',
            'image_url' => $transaction->product_image_url ?? null,
        ];
    }

    private function buildMessageViewData(Transaction $transaction): Collection
    {
        return $transaction->messages->map(function (ChatMessage $message): object {
            return (object) [
                'id' => $message->id,
                'user_id' => $message->user_id,
                'body' => $message->body,
                'image_url' => $this->resolveChatMessageImageUrl($message),
                'created_at' => $message->created_at,
                'edited_at' => $message->edited_at ?? null,
            ];
        });
    }

    private function shouldShowRatingModal(
        Request $request,
        Transaction $transaction,
        string $screenRole,
        int $userId
    ): bool {
        $alreadyRatedByMe = $transaction->ratings()
            ->where('rater_user_id', $userId)
            ->exists();

        if ($screenRole === self::SCREEN_ROLE_BUYER) {
            return (bool) $request->boolean('rate', false);
        }

        return (
            $transaction->status === self::STATUS_BUYER_COMPLETED
            || $transaction->buyer_completed_at !== null
        ) && !$alreadyRatedByMe;
    }

    private function draftSessionKey(int $transactionId): string
    {
        return "chat_draft_transaction_{$transactionId}";
    }

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