<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'rater_user_id',
        'ratee_user_id',
        'rating',
        'comment',
        'rated_at',
    ];

    protected $casts = [
        'rated_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_user_id');
    }

    public function ratee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_user_id');
    }
}