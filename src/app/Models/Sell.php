<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sell extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'category_id',
        'name',
        'brand',
        'price',
        'image_path',
        'condition',
        'description',
        'is_sold',
        'category_ids_json',
    ];

    protected $casts = [
        'price'   => 'integer',
        'is_sold' => 'boolean',
        'category_ids_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function latestPurchase()
    {
        return $this->hasOne(Purchase::class)->latestOfMany('purchased_at');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_sold', false);
    }

    public function scopeSold($query)
    {
        return $query->where('is_sold', true);
    }

    public function getThumbUrlAttribute(): string
    {
        $path = $this->image_path;

        if (!$path && $this->relationLoaded('product') && $this->product) {
            $path = $this->product->image_url
                ?? $this->product->image_path
                ?? null;
        }

        if (!empty($path)) {
            if (Str::startsWith($path, ['http://', 'https://'])) {
                return $path;
            }
            return asset('storage/' . ltrim($path, '/'));
        }

        return asset('img/no-image.png');
    }
}
