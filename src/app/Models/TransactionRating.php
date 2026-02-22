<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionRating extends Model
{
    use HasFactory;

    /**
     * 必要に応じてテーブル名を明示
     * （Laravel規約どおりなら省略可）
     */
    protected $table = 'transaction_ratings';

    /**
     * 一括代入許可
     */
    protected $fillable = [
        'transaction_id',
        'rater_user_id',
        'ratee_user_id',
        'rating',
        'comment',
        'rated_at',
    ];

    /**
     * 型変換
     */
    protected $casts = [
        'transaction_id' => 'integer',
        'rater_user_id'  => 'integer',
        'ratee_user_id'  => 'integer',
        'rating'         => 'integer',
        'rated_at'       => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    /**
     * 取引
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * 評価したユーザー
     */
    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_user_id');
    }

    /**
     * 評価されたユーザー
     */
    public function ratee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_user_id');
    }
}