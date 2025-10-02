<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = [
        'user_id',      // 購入者
        'sell_id',      // 出品
        'amount',       // 購入金額（見積もり）
        'purchased_at',
    ];

    public function payment()
    {
        // 1:1 の想定なら hasOne
        return $this->hasOne(Payment::class);
    }

    // もし分割払い等で複数支払いを許容するなら hasMany にする
    // public function payments() { return $this->hasMany(Payment::class); }

    public function sell()
    {
        return $this->belongsTo(Sell::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
