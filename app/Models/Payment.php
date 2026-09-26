<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    public const SOURCE_RAZORPAY = 'razorpay';

    public const SOURCE_MANUAL = 'manual';

    /** Offline payment methods an admin can record. */
    public const MANUAL_METHODS = [
        'cash' => 'Cash',
        'upi' => 'UPI',
        'bank_transfer' => 'Bank transfer',
        'cheque' => 'Cheque',
        'card' => 'Card (POS)',
        'other' => 'Other',
    ];

    protected $fillable = [
        'user_id', 'membership_plan_id', 'source', 'invoice_no', 'razorpay_order_id', 'razorpay_payment_id',
        'razorpay_signature', 'amount', 'currency', 'status', 'method', 'failure_reason', 'meta', 'paid_at',
        'reference', 'notes', 'recorded_by',
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

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by')->withTrashed();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isManual(): bool
    {
        return $this->source === self::SOURCE_MANUAL;
    }

    public function methodLabel(): string
    {
        return $this->isManual()
            ? (self::MANUAL_METHODS[$this->method] ?? ucfirst((string) $this->method))
            : strtoupper($this->method ?? 'Online');
    }

    public function statusBadge(): string
    {
        return ['paid' => 'success', 'failed' => 'danger', 'created' => 'warning'][$this->status] ?? 'secondary';
    }
}
