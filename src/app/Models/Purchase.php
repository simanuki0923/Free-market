<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use HasFactory;

    // ★ マイグレーションの列名に合わせる
    protected $fillable = [
        'user_id',
        'product_id',
        'price',                 // マイグレーションに合わせる（decimalでもintegerでもOK）
        'payment_method',        // 'credit_card' 等
        'status',                // 'paid' など
        'stripe_session_id',     // 使わなければ未使用のままでOK
        'shipping_recipient',
        'shipping_postal_code',
        'shipping_prefecture',
        'shipping_city',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_phone',
        'purchase_date',         // 使う場合
    ];

    protected $casts = [
        'purchase_date' => 'datetime',  // ← キャスト対象も修正
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
