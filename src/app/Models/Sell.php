<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sell extends Model
{
    use HasFactory;

    protected $table = 'sells';

    // 表示ラベル→数値の正規化に使う（フォームが日本語でも数値でもOK）
    public const CONDITION_MAP = [
        '新品'                 => 1,
        '未使用に近い'         => 2,
        '目立った傷や汚れなし' => 3,
        'やや傷や汚れあり'     => 4,
        '傷や汚れあり'         => 5,
        '1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,
    ];

    protected $fillable = [
        'user_id','product_id','name','brand','price','image_path','condition','description','is_sold',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_sold' => 'boolean',
        'condition' => 'integer',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'product_id'); }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_sell', 'sell_id', 'category_id')->withTimestamps();
    }

    /** 画像URL（storage/外部URL/no-image対応） */
    public function getThumbUrlAttribute(): string
    {
        if (!$this->image_path) return asset('img/no-image.png');
        return str_starts_with($this->image_path, 'http')
            ? $this->image_path : asset('storage/'.$this->image_path);
    }

    /** 条件の正規化（Controller から使用） */
    public static function normalizeCondition(null|string|int $value): ?int
    {
        if ($value === null || $value === '') return null;
        $key = is_string($value) ? trim($value) : $value;
        if (is_string($key) && array_key_exists($key, self::CONDITION_MAP)) return self::CONDITION_MAP[$key];
        $n = (int)$value;
        return $n > 0 ? $n : null;
    }

    /** ログインユーザー絞り込み */
    public function scopeByUser($q, int $userId) { return $q->where('user_id', $userId); }
}
