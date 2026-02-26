<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sell_id',
        'amount',
        'payment_method',
        'purchased_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'purchased_at' => 'datetime',
    ];

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function sell(): BelongsTo
    {
        return $this->belongsTo(Sell::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}