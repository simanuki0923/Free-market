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

    public function __construct(Transaction $transaction)
    {
        $transaction->loadMissing(['seller', 'buyer', 'product']);

        $this->transaction = $transaction;
    }

    public function envelope(): Envelope
    {
        $productName = $this->transaction->product?->name ?? '商品';

        return new Envelope(
            subject: '【取引完了通知】購入者が取引を完了しました（' . $productName . '）',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.transaction-completed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}