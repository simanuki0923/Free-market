<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatMessageStoreRequest;
use App\Http\Requests\ChatMessageUpdateRequest;
use App\Models\ChatMessage;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatMessageController extends Controller
{
    public function store(ChatMessageStoreRequest $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();

        $this->authorizeParticipant($transaction, (int) $user->id);

        $validated = $request->validated();

        $body = (string) ($validated['body'] ?? '');
        $imagePath = $this->storeUploadedChatImage($request);

        if ($body === '' && $imagePath === null) {
            return back()
                ->withErrors(['body' => '本文または画像を入力してください。'])
                ->withInput();
        }

        ChatMessage::create([
            'transaction_id' => (int) $transaction->id,
            'user_id' => (int) $user->id,
            'body' => $body !== '' ? $body : null,
            'image_path' => $imagePath,
        ]);

        $request->session()->forget($this->draftSessionKey((int) $transaction->id));
        $this->touchTransactionLastMessageAt($transaction);

        return $this->redirectToChatByUserRole($transaction, (int) $user->id)
            ->with('status', 'メッセージを送信しました。');
    }

    public function update(ChatMessageUpdateRequest $request, ChatMessage $message): RedirectResponse
    {
        $transaction = $this->resolveMessageTransaction($message);
        $userId = (int) $request->user()->id;

        $this->authorizeParticipant($transaction, $userId);
        $this->authorizeMessageOwner($message, $userId);

        $validated = $request->validated();
        $newBody = (string) $validated['body'];

        if ($newBody === '') {
            return back()
                ->withErrors(['body' => '本文を入力してください。'])
                ->withInput();
        }

        $message->update([
            'body' => $newBody,
            'edited_at' => now(),
        ]);

        $this->touchTransactionLastMessageAt($transaction);

        return $this->redirectToChatByUserRole($transaction, $userId)
            ->with('status', 'メッセージを更新しました。');
    }

    public function destroy(\Illuminate\Http\Request $request, ChatMessage $message): RedirectResponse
    {
        $transaction = $this->resolveMessageTransaction($message);
        $userId = (int) $request->user()->id;

        $this->authorizeParticipant($transaction, $userId);
        $this->authorizeMessageOwner($message, $userId);

        $this->deleteChatImageIfStoredLocally($message->image_path ?? null, (int) $message->id);

        $message->delete();
        $this->touchTransactionLastMessageAt($transaction);

        return $this->redirectToChatByUserRole($transaction, $userId)
            ->with('status', 'メッセージを削除しました。');
    }

    private function storeUploadedChatImage($request): ?string
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('chat-images', 'public');
    }

    private function resolveMessageTransaction(ChatMessage $message): Transaction
    {
        return $message->transaction()->firstOrFail();
    }

    private function touchTransactionLastMessageAt(Transaction $transaction): void
    {
        $transaction->update([
            'last_message_at' => now(),
        ]);
    }

    private function deleteChatImageIfStoredLocally(?string $imagePath, int $messageId): void
    {
        if (empty($imagePath)) {
            return;
        }

        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return;
        }

        try {
            Storage::disk('public')->delete(ltrim($imagePath, '/'));
        } catch (\Throwable $exception) {
            Log::warning('チャット画像削除失敗', [
                'chat_message_id' => $messageId,
                'image_path' => $imagePath,
                'error' => $exception->getMessage(),
            ]);
        }
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

    private function authorizeMessageOwner(ChatMessage $message, int $userId): void
    {
        if ((int) $message->user_id !== $userId) {
            abort(403, 'このメッセージを操作できません。');
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