<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','category_id','name','brand','price','image_path',
        'condition','description','is_sold',
    ];

    protected $casts = [
        'price'   => 'integer',
        'is_sold' => 'boolean',
    ];

    /** 作成者 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** カテゴリ */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** コメント（product_id 外部キー） */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** お気に入り（favorites テーブル直参照） */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /** この商品をお気に入りに入れたユーザー（pivot: favorites） */
    public function favoredByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /** 1:1で対応する出品 */
    public function sell(): HasOne
    {
        return $this->hasOne(Sell::class);
    }
}
