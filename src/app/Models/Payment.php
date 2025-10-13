<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'purchase_id',
        'payment_method',
        'provider_txn_id',
        'paid_amount',
        'paid_at',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
