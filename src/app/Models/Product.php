<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'brand',
        'price',
        'image_url',
        'is_sold',
        'condition',
        'description',
    ];

    protected $casts = [
        'is_sold' => 'boolean',
        'price'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favoredByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    // ログインユーザー自身の出品を除外するための補助スコープ
    public function scopeExcludeOwner(Builder $query, ?int $userId): Builder
    {
        if ($userId) {
            $query->where('user_id', '!=', $userId);
        }
        return $query;
    }

    // 指定ユーザーの「お気に入り」商品のみに絞るスコープ
    public function scopeOnlyFavoritesOf(Builder $query, int $userId): Builder
    {
        return $query->whereIn('id', function ($q) use ($userId) {
            $q->from('favorites')->select('product_id')->where('user_id', $userId);
        });
    }
}

