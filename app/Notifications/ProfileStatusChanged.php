<?php

namespace App\Notifications;

use App\Models\Profile;

class ProfileStatusChanged extends AppNotification
{
    protected bool $sendMail = true;

    public function __construct(private Profile $profile) {}

    protected function title(): string
    {
        return $this->profile->approval_status === Profile::STATUS_APPROVED ? 'Your profile is live' : 'Profile needs changes';
    }

    protected function message(): string
    {
        return $this->profile->approval_status === Profile::STATUS_APPROVED
            ? 'Your profile has been approved and is now visible to other members.'
            : 'Your profile was not approved. Reason: '.($this->profile->rejection_reason ?: 'Please review your details.');
    }

    protected function url(): ?string
    {
        return route('profile.edit');
    }

    protected function icon(): string
    {
        return 'bi-person-check';
    }
}
