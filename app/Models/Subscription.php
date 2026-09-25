<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'membership_plan_id', 'payment_id', 'amount', 'starts_at', 'expires_at',
        'status', 'contact_views_used', 'expiry_reminder_sent',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'amount' => 'decimal:2',
            'expiry_reminder_sent' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }

    public function daysLeft(): int
    {
        return max(0, (int) ceil(now()->diffInDays($this->expires_at, false)));
    }

    public function contactViewsRemaining(): int
    {
        return max(0, (int) $this->plan->contact_views_limit - $this->contact_views_used);
    }

    public function statusBadge(): string
    {
        if ($this->status === 'active' && $this->expires_at->isPast()) {
            return 'secondary';
        }

        return ['active' => 'success', 'cancelled' => 'danger'][$this->status] ?? 'secondary';
    }
}
