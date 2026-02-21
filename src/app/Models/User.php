<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name','email','password',
    ];

    protected $hidden = [
        'password','remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = mb_strtolower($value);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function sells(): HasMany
    {
        return $this->hasMany(Sell::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function sellerTransactions(): HasMany
{
    return $this->hasMany(Transaction::class, 'seller_id');
}

public function buyerTransactions(): HasMany
{
    return $this->hasMany(Transaction::class, 'buyer_id');
}

public function chatMessages(): HasMany
{
    return $this->hasMany(ChatMessage::class);
}

public function givenTransactionRatings(): HasMany
{
    return $this->hasMany(TransactionRating::class, 'rater_user_id');
}

public function receivedTransactionRatings(): HasMany
{
    return $this->hasMany(TransactionRating::class, 'ratee_user_id');
}

/**
 * 要件: 評価平均（四捨五入）
 * 評価なしは null を返す（表示しない）
 */
public function getRoundedRatingAverageAttribute(): ?int
{
    $avg = $this->receivedTransactionRatings()->avg('rating');

    if ($avg === null) {
        return null;
    }

    return (int) round($avg, 0, PHP_ROUND_HALF_UP);
}
}
