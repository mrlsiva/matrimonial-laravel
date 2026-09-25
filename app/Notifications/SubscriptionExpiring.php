<?php

namespace App\Notifications;

use App\Models\Subscription;

class SubscriptionExpiring extends AppNotification
{
    protected bool $sendMail = true;

    public function __construct(private Subscription $subscription, private bool $expired = false) {}

    protected function title(): string
    {
        return $this->expired ? 'Membership expired' : 'Membership expiring soon';
    }

    protected function message(): string
    {
        $plan = $this->subscription->plan->name;

        return $this->expired
            ? "Your {$plan} membership has expired. Renew to keep chatting and viewing contacts."
            : "Your {$plan} membership expires on {$this->subscription->expires_at->format('d M Y')}. Renew now to avoid interruption.";
    }

    protected function url(): ?string
    {
        return route('membership.index');
    }

    protected function icon(): string
    {
        return 'bi-hourglass-split';
    }
}
