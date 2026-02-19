<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Illuminate\Support\Facades\Mail;
use Modules\Notification\Enums\NotificationStatus;
use Modules\Notification\Models\Notification;
use Modules\Notification\Repositories\NotificationLogRepository;

/**
 * Email Notification Service
 *
 * Handles sending notifications via email using Laravel Mail
 */
class EmailNotificationService
{
    public function __construct(
        private NotificationLogRepository $logRepository
    ) {}

    /**
     * Send notification via email
     */
    public function send(Notification $notification): bool
    {
        try {
            // Get recipient email
            $recipient = $notification->user->email;

            if (empty($recipient)) {
                throw new \InvalidArgumentException('User does not have an email address');
            }

            // Send email using Laravel Mail
            Mail::html(
                $notification->body,
                function ($message) use ($notification, $recipient) {
                    $message->to($recipient)
                        ->subject($notification->subject);

                    // Set from address if configured
                    $from = config('mail.from.address');
                    $fromName = config('mail.from.name');
                    if ($from) {
                        $message->from($from, $fromName);
                    }

                    // Handle priority
                    if ($notification->priority->value === 'urgent' || $notification->priority->value === 'high') {
                        $message->priority(1);
                    }
                }
            );

            // Log success
            $this->logSuccess($notification, $recipient);

            return true;
        } catch (\Exception $e) {
            // Log failure
            $this->logFailure($notification, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Log successful send
     */
    private function logSuccess(Notification $notification, string $recipient): void
    {
        $this->logRepository->create([
            'tenant_id' => $notification->tenant_id,
            'organization_id' => $notification->organization_id,
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'type' => $notification->type,
            'status' => NotificationStatus::SENT,
            'channel' => 'email',
            'recipient' => $recipient,
            'subject' => $notification->subject,
            'sent_at' => now(),
            'metadata' => [
                'sent_via' => 'laravel_mail',
                'mailer' => config('mail.default'),
            ],
        ]);
    }

    /**
     * Log failed send
     */
    private function logFailure(Notification $notification, string $errorMessage): void
    {
        $recipient = $notification->user->email ?? 'unknown';

        $this->logRepository->create([
            'tenant_id' => $notification->tenant_id,
            'organization_id' => $notification->organization_id,
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'type' => $notification->type,
            'status' => NotificationStatus::FAILED,
            'channel' => 'email',
            'recipient' => $recipient,
            'subject' => $notification->subject,
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'metadata' => [
                'sent_via' => 'laravel_mail',
                'mailer' => config('mail.default'),
            ],
        ]);
    }
}
