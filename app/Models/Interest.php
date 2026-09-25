<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interest extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'status', 'message', 'responded_at'];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /** Most recent interest between two users in either direction. */
    public static function between(int $a, int $b): ?self
    {
        return static::where(fn ($q) => $q->where('sender_id', $a)->where('receiver_id', $b))
            ->orWhere(fn ($q) => $q->where('sender_id', $b)->where('receiver_id', $a))
            ->latest()
            ->first();
    }

    public function statusBadge(): string
    {
        return ['accepted' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'][$this->status] ?? 'warning';
    }
}
