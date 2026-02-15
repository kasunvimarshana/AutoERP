<?php

declare(strict_types=1);

namespace App\Services\WebPush;

use Illuminate\Notifications\Notification;

/**
 * Web Push Notification Channel
 * 
 * Custom notification channel for sending native push notifications
 */
class WebPushChannel
{
    public function __construct(
        private readonly WebPushService $webPushService
    ) {}

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toWebPush')) {
            return;
        }

        $payload = $notification->toWebPush($notifiable);

        if (empty($payload)) {
            return;
        }

        $this->webPushService->sendToUser($notifiable, $payload);
    }
}
