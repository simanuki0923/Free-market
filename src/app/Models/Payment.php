<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'payment_method',
        'provider_txn_id',
        'paid_amount',
        'paid_at',
    ];

    protected $casts = [
        'purchase_id' => 'integer',
        'paid_amount' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}