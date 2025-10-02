<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'purchase_id',       // FK
        'payment_method',    // 'convenience_store' | 'credit_card' | 'bank_transfer'
        'provider_txn_id',   // Stripeのpayment_intent id 等
        'paid_amount',       // 実際に支払われた金額
        'paid_at',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
