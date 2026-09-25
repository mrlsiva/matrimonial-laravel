<?php

namespace App\Services;

use App\Models\ContactView;
use App\Models\Interest;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentSuccessful;
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
    public function activate(User $user, MembershipPlan $plan, ?Payment $payment = null): Subscription
    {
        $current = $user->activeSubscription()->first();
        $extendFrom = now();

        if ($current && $current->membership_plan_id === $plan->id) {
            $extendFrom = $current->expires_at->copy();
            $current->update(['status' => 'expired', 'expires_at' => now()]);
        } elseif ($current) {
            $current->update(['status' => 'cancelled']);
        }

        return Subscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'payment_id' => $payment?->id,
            'amount' => $payment?->amount ?? $plan->price,
            'starts_at' => now(),
            'expires_at' => $extendFrom->addDays($plan->duration_days),
            'status' => 'active',
        ]);
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
