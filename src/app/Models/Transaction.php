<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'sell_id',
        'product_id',
        'seller_id',
        'buyer_id',
        'status',
        'last_message_at',
        'buyer_completed_at',
        'completed_at',
    ];

    protected $casts = [
        'last_message_at'    => 'datetime',
        'buyer_completed_at' => 'datetime',
        'completed_at'       => 'datetime',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function sell(): BelongsTo
    {
        return $this->belongsTo(Sell::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->latest('created_at');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(TransactionRating::class);
    }

    public function scopeForParticipant(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('seller_id', $userId)
              ->orWhere('buyer_id', $userId);
        });
    }

    public function scopeWithUnreadCountFor(Builder $query, int $userId): Builder
    {
        return $query->withCount([
            'messages as unread_messages_count' => function ($q) use ($userId) {
                $q->where('user_id', '!=', $userId)
                  ->whereDoesntHave('reads', function ($rq) use ($userId) {
                      $rq->where('user_id', $userId);
                  });
            },
        ]);
    }

    public function getProductImageUrlAttribute(): ?string
    {
        $path = $this->product?->image_path;

        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}