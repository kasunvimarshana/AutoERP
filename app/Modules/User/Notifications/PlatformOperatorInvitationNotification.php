<?php

declare(strict_types=1);

namespace Modules\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PlatformOperatorInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $operatorName,
        private readonly string $acceptanceUrl,
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
            ->subject('Complete your AutoERP platform operator account')
            ->greeting("Hello {$this->operatorName},")
            ->line('You have been invited to the AutoERP platform control plane.')
            ->line('Use the secure link below to choose your own password before signing in.')
            ->action('Complete platform operator registration', $this->acceptanceUrl)
            ->line("This invitation expires at {$this->expiresAt}.")
            ->line('Ignore this message if you were not expecting this invitation.');
    }
}
