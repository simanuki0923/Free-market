<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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
        'price'            => 'integer',
        'is_sold'          => 'boolean',
        'category_ids_json'=> 'array',
    ];

    /**
     * 商品キーワード検索（部分一致）
     * - name / brand / description を対象に LIKE 検索
     */
    public function scopeKeywordSearch(Builder $query, ?string $keyword): Builder
    {
        $keyword = trim((string) $keyword);

        if ($keyword !== '') {
            $like = '%' . $keyword . '%';

            $query->where(function (Builder $q) use ($like) {
                $q->where('name', 'LIKE', $like)
                  ->orWhere('brand', 'LIKE', $like)
                  ->orWhere('description', 'LIKE', $like);
            });
        }

        return $query;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoredByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function sell(): HasOne
    {
        return $this->hasOne(Sell::class);
    }
}
