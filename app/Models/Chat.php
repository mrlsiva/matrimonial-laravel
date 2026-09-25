<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chat extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'message', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function scopeConversation(Builder $query, int $a, int $b): Builder
    {
        return $query->where(fn ($q) => $q
            ->where(fn ($q) => $q->where('sender_id', $a)->where('receiver_id', $b))
            ->orWhere(fn ($q) => $q->where('sender_id', $b)->where('receiver_id', $a)));
    }
}
