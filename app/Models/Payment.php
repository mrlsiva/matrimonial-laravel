<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'membership_plan_id', 'invoice_no', 'razorpay_order_id', 'razorpay_payment_id',
        'razorpay_signature', 'amount', 'currency', 'status', 'method', 'failure_reason', 'meta', 'paid_at',
    ];

    protected $hidden = ['razorpay_signature'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
            'paid_at' => 'datetime',
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

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function statusBadge(): string
    {
        return ['paid' => 'success', 'failed' => 'danger', 'created' => 'warning'][$this->status] ?? 'secondary';
    }
}
