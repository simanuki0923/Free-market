<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'slug'];

    /**
     * 単一カテゴリ（products.category_id）向けの 1:N
     * 既存のフォールバック表示で使用される想定
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Category に紐づく Sell（必要なら）
     */
    public function sells(): HasMany
    {
        return $this->hasMany(Sell::class);
    }
}
