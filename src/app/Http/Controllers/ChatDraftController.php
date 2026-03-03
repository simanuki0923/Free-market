<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatDraftStoreRequest;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;

class ChatDraftController extends Controller
{
    public function store(ChatDraftStoreRequest $request, Transaction $transaction): RedirectResponse
    {
        $userId = (int) $request->user()->id;

        $this->authorizeParticipant($transaction, $userId);

        $validated = $request->validated();
        $draftBody = (string) ($validated['body'] ?? '');

        $request->session()->put($this->draftSessionKey((int) $transaction->id), $draftBody);

        return $this->redirectToChatByUserRole($transaction, $userId)
            ->with('status', '下書きを保存しました。');
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

    private function redirectToChatByUserRole(Transaction $transaction, int $userId): RedirectResponse
    {
        $routeName = (int) $transaction->buyer_id === $userId
            ? 'chat.buyer'
            : 'chat.seller';

        return redirect()->route($routeName, [
            'transaction' => $transaction->id,
        ]);
    }

    private function draftSessionKey(int $transactionId): string
    {
        return "chat_draft_transaction_{$transactionId}";
    }
}