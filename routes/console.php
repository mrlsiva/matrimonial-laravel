<?php

use App\Models\Subscription;
use App\Notifications\SubscriptionExpiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('subscriptions:process', function () {
    // Expire subscriptions past their end date.
    $expired = Subscription::with(['user', 'plan'])->where('status', 'active')->where('expires_at', '<=', now())->get();
    foreach ($expired as $subscription) {
        $subscription->update(['status' => 'expired']);
        $subscription->user?->notify(new SubscriptionExpiring($subscription, expired: true));
    }

    // Remind members three days before expiry (once).
    $expiring = Subscription::with(['user', 'plan'])
        ->where('status', 'active')
        ->where('expiry_reminder_sent', false)
        ->whereBetween('expires_at', [now(), now()->addDays(3)])
        ->get();
    foreach ($expiring as $subscription) {
        $subscription->user?->notify(new SubscriptionExpiring($subscription));
        $subscription->update(['expiry_reminder_sent' => true]);
    }

    $this->info("Expired: {$expired->count()}, reminders sent: {$expiring->count()}");
})->purpose('Expire finished subscriptions and send renewal reminders');

Schedule::command('subscriptions:process')->hourly()->withoutOverlapping();
Schedule::command('model:prune', ['--model' => [App\Models\OtpCode::class]])->daily();
