<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;              // ★ 追加
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail // ★ 追加
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name','email','password',
    ];

    protected $hidden = [
        'password','remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        // Laravel 11/12 ならパスワードは自動ハッシュなので記述不要ですが、
        // もし古い設定の場合は: 'password' => 'hashed',
    ];

    // （任意・推奨）メールを小文字で正規化して保存
    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = mb_strtolower($value);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function sells(): HasMany
    {
        return $this->hasMany(Sell::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
