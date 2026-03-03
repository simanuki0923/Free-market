<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'body',
        'image_path',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    protected $appends = [
        'image_url',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ChatMessageRead::class, 'chat_message_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        if (
            str_starts_with($this->image_path, 'http://')
            || str_starts_with($this->image_path, 'https://')
        ) {
            return $this->image_path;
        }

        return asset('storage/' . ltrim($this->image_path, '/'));
    }
}