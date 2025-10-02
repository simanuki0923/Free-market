<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sell extends Model
{
    use HasFactory;

    /**
     * スキーマ想定：
     * - id, user_id, product_id, price, is_sold, image_path など
     * - （存在する場合のみ）category_id / name / brand / description / condition
     *   → プロジェクトの実マイグレーションに合わせてOK
     */
    protected $fillable = [
        'user_id', 'product_id',
        'category_id', // ※無いテーブルなら削除してOK
        'name',        // ※同上（productへ正規化されていれば未使用）
        'brand',       // ※同上
        'price',
        'image_path',
        'condition',   // ※同上
        'description', // ※同上
        'is_sold',
    ];

    protected $casts = [
        'price'   => 'integer',
        'is_sold' => 'boolean',
    ];

    /* ===============================
     |  Relations
     |===============================*/

    /** 出品者 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 元の商品 */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * カテゴリ（Sell 直下に category_id がある場合のみ）
     * 通常は product.category を辿る方が自然です。
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 購入レコード（1件だけ拾いたいとき）
     * - 「1出品=1購入」を想定する画面用のショートカット
     * - 実DBは複数許容なので、厳密には latestPurchase を使うのが安全
     */
    public function purchase(): HasOne
    {
        return $this->hasOne(Purchase::class);
    }

    /** 購入レコード（複数想定・集計等に使用） */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * 直近の購入（first sold などの画面用）
     * ※ Laravel 9+ の latestOfMany/oldestOfMany を使用
     */
    public function latestPurchase(): HasOne
    {
        return $this->hasOne(Purchase::class)->latestOfMany('purchased_at');
    }

    /* ===============================
     |  Scopes
     |===============================*/

    /** ユーザーで絞り込み（出品者） */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /** 販売中 */
    public function scopeAvailable($query)
    {
        return $query->where('is_sold', false);
    }

    /** 売り切れ */
    public function scopeSold($query)
    {
        return $query->where('is_sold', true);
    }

    /* ===============================
     |  Accessors (UIヘルパ)
     |===============================*/

    /**
     * サムネイルURL（外部URL / storage / no-image）
     * - Sell.image_path を優先
     * - 無ければ product 側の image_url / image_path をフォールバック
     */
    public function getThumbUrlAttribute(): string
    {
        // 1) Sell 側の image_path
        $path = $this->image_path;

        // 2) 無ければ Product 側の画像を拝借
        if (!$path && $this->relationLoaded('product') && $this->product) {
            $path = $this->product->image_url ?? $this->product->image_path ?? null;
        }

        if (!empty($path)) {
            // http(s) ならそのまま、相対なら storage パスへ
            if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) {
                return $path;
            }
            return asset('storage/' . ltrim($path, '/'));
        }

        return asset('img/no-image.png');
    }
}
