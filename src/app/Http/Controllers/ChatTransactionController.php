<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRatingStoreRequest;
use App\Mail\TransactionCompletedMail;
use App\Models\Transaction;
use App\Models\TransactionRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ChatTransactionController extends Controller
{
    private const STATUS_BUYER_COMPLETED = 'buyer_completed';
    private const STATUS_COMPLETED = 'completed';

    public function complete(Request $request, Transaction $transaction): RedirectResponse
    {
        $userId = (int) $request->user()->id;

        $this->authorizeParticipant($transaction, $userId);

        if ((int) $transaction->buyer_id !== $userId) {
            abort(403, '購入者のみ取引完了できます。');
        }

        if ($transaction->buyer_completed_at === null) {
            $transaction->update([
                'buyer_completed_at' => now(),
                'status' => self::STATUS_BUYER_COMPLETED,
            ]);

            $transaction->loadMissing(['seller', 'buyer', 'product']);
            $this->sendTransactionCompletedMailToSeller($transaction);
        }

        return redirect()->route('chat.buyer', [
            'transaction' => $transaction->id,
            'rate' => 1,
        ]);
    }

    public function rate(TransactionRatingStoreRequest $request, Transaction $transaction): RedirectResponse
    {
        $userId = (int) $request->user()->id;

        $this->authorizeParticipant($transaction, $userId);

        $buyerId = (int) ($transaction->buyer_id ?? 0);
        $sellerId = (int) ($transaction->seller_id ?? 0);

        if (!in_array($userId, [$buyerId, $sellerId], true)) {
            abort(403, 'この取引を評価できません。');
        }

        $rating = (int) $request->input('rating');
        $rateeUserId = $userId === $buyerId ? $sellerId : $buyerId;

        if ($rateeUserId <= 0) {
            return back()
                ->withErrors(['rating' => '評価対象ユーザーを特定できませんでした。'])
                ->withInput();
        }

        DB::transaction(function () use ($transaction, $userId, $rateeUserId, $rating, $buyerId, $sellerId): void {
            TransactionRating::updateOrCreate(
                [
                    'transaction_id' => (int) $transaction->id,
                    'rater_user_id' => $userId,
                ],
                [
                    'ratee_user_id' => $rateeUserId,
                    'rating' => $rating,
                    'rated_at' => now(),
                ]
            );

            $ratedUserIds = TransactionRating::query()
                ->where('transaction_id', (int) $transaction->id)
                ->pluck('rater_user_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();

            $bothRated = $ratedUserIds->contains($buyerId) && $ratedUserIds->contains($sellerId);

            if (!$bothRated || $transaction->status === self::STATUS_COMPLETED) {
                return;
            }

            $updatePayload = [
                'status' => self::STATUS_COMPLETED,
            ];

            if (empty($transaction->completed_at)) {
                $updatePayload['completed_at'] = now();
            }

            $transaction->update($updatePayload);
        });

        return redirect()
            ->route('item')
            ->with('status', '評価を送信しました。');
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

    private function sendTransactionCompletedMailToSeller(Transaction $transaction): void
    {
        if (empty($transaction->seller?->email)) {
            return;
        }

        try {
            Mail::to($transaction->seller->email)->send(new TransactionCompletedMail($transaction));
        } catch (\Throwable $exception) {
            Log::error('取引完了メール送信失敗', [
                'transaction_id' => $transaction->id,
                'seller_id' => $transaction->seller_id,
                'seller_email' => $transaction->seller?->email,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}