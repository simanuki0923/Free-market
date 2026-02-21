<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Transaction $transaction;

    /**
     * Create a new message instance.
     */
    public function __construct(Transaction $transaction)
    {
        // メール本文で seller / buyer / product を使うので、必要なら読み込む
        $transaction->loadMissing(['seller', 'buyer', 'product']);

        $this->transaction = $transaction;
    }

    /**
     * メール件名
     */
    public function envelope(): Envelope
    {
        $productName = $this->transaction->product?->name ?? '商品';

        return new Envelope(
            subject: '【取引完了通知】購入者が取引を完了しました（' . $productName . '）',
        );
    }

    /**
     * メール本文で使う Blade を指定
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.transaction-completed',
        );
    }

    /**
     * 添付ファイル（今回はなし）
     */
    public function attachments(): array
    {
        return [];
    }
}