<?php

declare(strict_types=1);

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Constants\RegistrationInvitationPurpose;

final class RegistrationInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $tenantName,
        private readonly string $registrationUrl,
        private readonly string $expiresAt,
        private readonly string $purpose,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        $isInitialAdministrator = $this->purpose === RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR;
        $accountLabel = $isInitialAdministrator ? 'administrator' : 'user';
        $greeting = $isInitialAdministrator
            ? 'You have been invited as the first tenant administrator.'
            : "You have been invited to join {$this->tenantName}.";

        return (new MailMessage)
            ->subject("Complete your {$this->tenantName} {$accountLabel} account")
            ->greeting($greeting)
            ->line("Complete your secure registration for {$this->tenantName}.")
            ->action('Complete account registration', $this->registrationUrl)
            ->line("This invitation expires at {$this->expiresAt}.")
            ->line('Ignore this message if you were not expecting this invitation.');
    }
}
