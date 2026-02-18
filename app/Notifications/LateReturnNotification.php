<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateReturnNotification extends Notification
{
    use Queueable;

    public function __construct(public int $fineAmount)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Late Book Return - Fine Applied')
            ->line("Your book was returned late.")
            ->line("A fine of {$this->fineAmount} CZK has been applied to your account.")
            ->line('Please pay your fine to continue borrowing books.');
    }
}
