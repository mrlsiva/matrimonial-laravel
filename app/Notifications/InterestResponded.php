<?php

namespace App\Notifications;

use App\Models\Interest;

class InterestResponded extends AppNotification
{
    protected bool $sendMail = true;

    public function __construct(private Interest $interest) {}

    protected function title(): string
    {
        return 'Interest '.$this->interest->status;
    }

    protected function message(): string
    {
        $name = $this->interest->receiver->name;

        return $this->interest->status === 'accepted'
            ? "{$name} accepted your interest. You can now chat and view contact details."
            : "{$name} has declined your interest.";
    }

    protected function url(): ?string
    {
        $profile = $this->interest->receiver->profile;

        return $profile ? route('profiles.show', $profile) : route('interests.index', ['tab' => 'sent']);
    }

    protected function icon(): string
    {
        return $this->interest->status === 'accepted' ? 'bi-check-circle-fill' : 'bi-x-circle';
    }
}
