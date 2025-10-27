<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sell_id',
        'amount',
        'payment_method',
        'purchased_at',
    ];

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function sell()
    {
        return $this->belongsTo(Sell::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
