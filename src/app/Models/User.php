<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setEmailAttribute(string $value): void
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

    public function getRoundedRatingAverageAttribute(): ?int
    {
        $averageRating = $this->receivedTransactionRatings()->avg('rating');

        if ($averageRating === null) {
            return null;
        }

        return (int) round((float) $averageRating, 0, PHP_ROUND_HALF_UP);
    }
}