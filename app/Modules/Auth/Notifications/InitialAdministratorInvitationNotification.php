<?php

declare(strict_types=1);

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InitialAdministratorInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $tenantName,
        private readonly string $registrationUrl,
        private readonly string $expiresAt,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Complete your {$this->tenantName} administrator account")
            ->greeting('You have been invited as the first tenant administrator.')
            ->line("Complete the secure registration for {$this->tenantName}.")
            ->action('Complete administrator registration', $this->registrationUrl)
            ->line("This invitation expires at {$this->expiresAt}.")
            ->line('Ignore this message if you were not expecting this invitation.');
    }
}
