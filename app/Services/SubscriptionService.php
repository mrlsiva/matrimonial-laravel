<?php

namespace App\Services;

use App\Models\ContactView;
use App\Models\Interest;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentSuccessful;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Mark a payment as paid and activate the plan. Idempotent: safe to call from both
     * the checkout callback and the Razorpay webhook.
     */
    public function completePayment(Payment $payment, string $paymentId, ?string $signature = null, ?string $method = null): Subscription
    {
        $subscription = DB::transaction(function () use ($payment, $paymentId, $signature, $method) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if ($payment->isPaid() && $payment->subscription) {
                return $payment->subscription;
            }

            $payment->update([
                'status' => 'paid',
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature ?? $payment->razorpay_signature,
                'method' => $method ?? $payment->method,
                'failure_reason' => null,
                'paid_at' => now(),
                'invoice_no' => $payment->invoice_no ?? sprintf('INV-%s-%06d', now()->format('Ym'), $payment->id),
            ]);

            return $this->activate($payment->user, $payment->plan, $payment);
        });

        $payment->user->notify(new PaymentSuccessful($payment->fresh()));

        return $subscription;
    }

    /**
     * Renewing the same plan extends from the current expiry; switching plans starts now
     * and cancels the previous subscription.
     */
    public function activate(User $user, MembershipPlan $plan, ?Payment $payment = null, ?CarbonInterface $startsAt = null): Subscription
    {
        $current = $user->activeSubscription()->first();
        $startsAt = $startsAt ? Carbon::parse($startsAt) : now();
        $extendFrom = $startsAt->copy();

        if ($current && $current->membership_plan_id === $plan->id) {
            $extendFrom = $current->expires_at->max($startsAt)->copy();
            $current->update(['status' => 'expired', 'expires_at' => now()]);
        } elseif ($current) {
            $current->update(['status' => 'cancelled']);
        }

        return Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'payment_id' => $payment?->id,
            'amount' => $payment?->amount ?? $plan->price,
            'starts_at' => $startsAt,
            'expires_at' => $extendFrom->addDays($plan->duration_days),
            'status' => 'active',
        ]);
    }

    /**
     * Record an offline (cash / UPI / bank) payment. A "paid" payment activates the plan
     * straight away, starting from the date the money was received.
     */
    public function recordManualPayment(User $user, MembershipPlan $plan, array $data, ?User $admin = null, bool $notify = true): Payment
    {
        $payment = DB::transaction(function () use ($user, $plan, $data, $admin) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'membership_plan_id' => $plan->id,
                'source' => Payment::SOURCE_MANUAL,
                'amount' => $data['amount'] ?? $plan->price,
                'currency' => config('services.razorpay.currency', 'INR'),
                'status' => $data['status'] ?? 'paid',
                'method' => $data['method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $admin?->id,
            ]);

            if ($payment->isPaid()) {
                $this->markManualPaid($payment, $data['paid_at'] ?? null);
            }

            return $payment;
        });

        if ($notify && $payment->isPaid()) {
            $user->notify(new PaymentSuccessful($payment->fresh('plan')));
        }

        return $payment;
    }

    /** Apply admin edits to a manual payment, keeping its subscription in step with the status. */
    public function updateManualPayment(Payment $payment, array $data, bool $notify = true): Payment
    {
        $becamePaid = DB::transaction(function () use ($payment, $data) {
            $wasPaid = $payment->isPaid();
            $payment->fill(collect($data)->only(['amount', 'method', 'reference', 'notes', 'status'])->all());
            if (! $wasPaid && isset($data['membership_plan_id'])) {
                $payment->membership_plan_id = $data['membership_plan_id'];
            }
            $payment->save();

            if (! $wasPaid && $payment->isPaid()) {
                $this->markManualPaid($payment, $data['paid_at'] ?? null);

                return true;
            }

            if ($wasPaid && ! $payment->isPaid()) {
                // Money not received after all: withdraw the plan it granted.
                $payment->subscription?->update(['status' => 'cancelled']);
                $payment->update(['paid_at' => null]);
            } elseif ($payment->isPaid()) {
                if (! empty($data['paid_at'])) {
                    $payment->update(['paid_at' => $data['paid_at']]);
                }
                $subscription = $payment->subscription;
                if ($subscription) {
                    $subscription->update(array_filter([
                        'amount' => $payment->amount,
                        'starts_at' => $data['starts_at'] ?? null,
                        'expires_at' => $data['expires_at'] ?? null,
                    ]));
                }
            }

            return false;
        });

        if ($notify && $becamePaid) {
            $payment->user->notify(new PaymentSuccessful($payment->fresh('plan')));
        }

        return $payment->fresh(['plan', 'subscription']);
    }

    private function markManualPaid(Payment $payment, mixed $paidAt): void
    {
        $paidAt = $paidAt ? Carbon::parse($paidAt) : now();
        $payment->update([
            'status' => 'paid',
            'paid_at' => $paidAt,
            'failure_reason' => null,
            'invoice_no' => $payment->invoice_no ?? sprintf('INV-%s-%06d', $paidAt->format('Ym'), $payment->id),
        ]);

        // A back-dated receipt starts the plan on the day the money was received.
        $this->activate($payment->user, $payment->plan, $payment, $paidAt->isToday() ? now() : $paidAt);
    }

    /** Plan limits in effect for the user (paid plan, else the free plan). */
    public function effectivePlan(User $user): ?MembershipPlan
    {
        return $user->currentPlan() ?? MembershipPlan::free();
    }

    public function interestsRemainingToday(User $user): ?int
    {
        $limit = $this->effectivePlan($user)?->daily_interest_limit ?? config('matrimony.free_daily_interest_limit');
        if ($limit === null) {
            return null; // unlimited
        }

        $sentToday = Interest::where('sender_id', $user->id)->whereDate('created_at', today())->count();

        return max(0, $limit - $sentToday);
    }

    /**
     * Contact details are visible to both parties after an accepted interest, or to
     * premium members while they have contact views left (a repeat view is free).
     */
    public function canViewContact(User $viewer, User $owner): bool
    {
        if ($viewer->id === $owner->id || $viewer->hasAcceptedInterestWith($owner)) {
            return true;
        }

        return ContactView::where('viewer_id', $viewer->id)->where('viewed_user_id', $owner->id)->exists();
    }

    /** Consume one contact view. Returns false when the plan does not allow it. */
    public function useContactView(User $viewer, User $owner): bool
    {
        if ($this->canViewContact($viewer, $owner)) {
            return true;
        }

        $subscription = $viewer->activeSubscription()->with('plan')->first();
        if (! $subscription || $subscription->contactViewsRemaining() < 1) {
            return false;
        }

        DB::transaction(function () use ($viewer, $owner, $subscription) {
            ContactView::create(['viewer_id' => $viewer->id, 'viewed_user_id' => $owner->id]);
            $subscription->increment('contact_views_used');
        });

        return true;
    }
}
