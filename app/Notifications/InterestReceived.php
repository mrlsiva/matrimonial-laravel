<?php

namespace App\Notifications;

use App\Models\Interest;

class InterestReceived extends AppNotification
{
    protected bool $sendMail = true;

    public function __construct(private Interest $interest) {}

    protected function title(): string
    {
        return 'New interest received';
    }

    protected function message(): string
    {
        $profile = $this->interest->sender->profile;

        return "{$this->interest->sender->name} ({$profile?->profile_code}) is interested in your profile.";
    }

    protected function url(): ?string
    {
        return route('interests.index', ['tab' => 'received']);
    }

    protected function icon(): string
    {
        return 'bi-heart-fill';
    }
}
