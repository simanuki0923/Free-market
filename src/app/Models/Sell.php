<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Sell extends Model
{
    use HasFactory;

    protected $table = 'sells';

    /** 表示用ラベル（1..6） */
    public const CONDITION_LABELS = [
        1 => '新品・未使用',
        2 => '未使用に近い',
        3 => '目立った傷や汚れなし',
        4 => 'やや傷や汚れあり',
        5 => '傷や汚れあり',
        6 => '全体的に状態が悪い',
    ];

    /**
     * 入力→数値 正規化用の別名マップ（フォームのゆらぎ対策）
     * キーは受け取りうる文字列、値は保存する数値
     */
    public const CONDITION_MAP = [
        // 正式ラベル
        '新品・未使用'         => 1,
        '未使用に近い'         => 2,
        '目立った傷や汚れなし' => 3,
        'やや傷や汚れあり'     => 4,
        '傷や汚れあり'         => 5,
        '全体的に状態が悪い'   => 6,

        // よくある略称/別名
        '新品'                 => 1,
        '未使用'               => 1,
        '未使用品'             => 1,

        // 数値文字列も許容
        '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6,
    ];

    protected $fillable = [
        'user_id', 'product_id', 'name', 'brand', 'price',
        'image_path', 'condition', 'description', 'is_sold',
    ];

    protected $casts = [
        'price'     => 'integer',
        'is_sold'   => 'boolean',
        'condition' => 'integer',
    ];

    /** リレーション */
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'product_id'); }

    public function categories(): BelongsToMany
    {
        // pivot: category_sell(sell_id, category_id)
        return $this->belongsToMany(Category::class, 'category_sell', 'sell_id', 'category_id')
                    ->withTimestamps();
    }

    /** サムネイルURL（storage/外部URL/no-image対応） */
    public function getThumbUrlAttribute(): string
    {
        if (!$this->image_path) return asset('img/no-image.png');
        return Str::startsWith($this->image_path, ['http://','https://'])
            ? $this->image_path
            : asset('storage/'.$this->image_path);
    }

    /** 保存前に状態を数値へ正規化（コントローラ側での変換が不要に） */
    public function setConditionAttribute($value): void
    {
        $this->attributes['condition'] = self::normalizeCondition($value);
    }

    /** 数値→ラベル（$sell->condition_label で取得） */
    public function getConditionLabelAttribute(): ?string
    {
        $c = $this->condition;
        return $c ? (self::CONDITION_LABELS[$c] ?? null) : null;
    }

    /** 文字列/数値どちらでも受け取り、1..6 の数値へ正規化 */
    public static function normalizeCondition(null|string|int $value): ?int
    {
        if ($value === null || $value === '') return null;

        // 数値/数値文字列なら範囲チェック
        if (is_numeric($value)) {
            $n = (int)$value;
            return ($n >= 1 && $n <= 6) ? $n : null;
        }

        // 文字列マップ照合（全角・前後空白対策）
        $key = trim((string)$value);
        if (array_key_exists($key, self::CONDITION_MAP)) {
            return self::CONDITION_MAP[$key];
        }

        return null; // 不明値は null（バリデーションで弾くことを推奨）
    }

    /** ログインユーザー絞り込み */
    public function scopeByUser($q, int $userId) { return $q->where('user_id', $userId); }
}
