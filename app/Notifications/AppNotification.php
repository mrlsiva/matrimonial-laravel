<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base in-app notification. Subclasses describe the message; every notification is stored
 * in the database (bell icon) and optionally emailed.
 */
abstract class AppNotification extends Notification
{
    use Queueable;

    protected bool $sendMail = false;

    abstract protected function title(): string;

    abstract protected function message(): string;

    abstract protected function url(): ?string;

    protected function icon(): string
    {
        return 'bi-bell';
    }

    public function via(object $notifiable): array
    {
        return $this->sendMail ? ['database', 'mail'] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => $this->url(),
            'icon' => $this->icon(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->title())->greeting('Hello '.$notifiable->name.',')->line($this->message());

        return $this->url() ? $mail->action('View details', $this->url()) : $mail;
    }
}
