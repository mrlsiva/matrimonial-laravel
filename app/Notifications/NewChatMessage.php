<?php

namespace App\Notifications;

use App\Models\User;

class NewChatMessage extends AppNotification
{
    public function __construct(private User $sender) {}

    protected function title(): string
    {
        return 'New message';
    }

    protected function message(): string
    {
        return "{$this->sender->name} sent you a message.";
    }

    protected function url(): ?string
    {
        return route('chat.show', $this->sender);
    }

    protected function icon(): string
    {
        return 'bi-chat-dots';
    }
}
