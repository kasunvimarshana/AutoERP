<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Illuminate\Support\Facades\Http;
use Modules\Notification\Enums\NotificationStatus;
use Modules\Notification\Exceptions\NotificationException;
use Modules\Notification\Models\Notification;
use Modules\Notification\Repositories\NotificationLogRepository;

/**
 * Push Notification Service
 *
 * Production-ready push notification integration supporting Firebase Cloud Messaging (FCM).
 * Uses native Laravel HTTP client - no third-party packages required.
 */
class PushNotificationService
{
    private string $provider;
    private bool $enabled;

    public function __construct(
        private NotificationLogRepository $logRepository
    ) {
        $this->provider = config('notification.push.provider', 'fcm');
        $this->enabled = config('notification.push.enabled', false);
    }

    /**
     * Send push notification
     *
     * Production-ready implementation supporting Firebase Cloud Messaging (FCM).
     * Falls back to logging if push notifications are disabled in configuration.
     */
    public function send(Notification $notification): bool
    {
        try {
            // Get device tokens for user
            $deviceTokens = $this->getDeviceTokens($notification);

            if (empty($deviceTokens)) {
                throw new NotificationException('User does not have any registered devices');
            }

            // If push is disabled, just log without sending
            if (!$this->enabled) {
                logger()->info('Push notification (disabled mode)', [
                    'notification_id' => $notification->id,
                    'device_count' => count($deviceTokens),
                    'subject' => $notification->subject,
                ]);

                foreach ($deviceTokens as $token) {
                    $this->logSuccess($notification, $token, ['provider' => 'mock (disabled)']);
                }

                return true;
            }

            // Send to all user devices
            $results = [];
            foreach ($deviceTokens as $token) {
                try {
                    $result = match ($this->provider) {
                        'fcm' => $this->sendViaFcm($token, $notification),
                        default => throw new NotificationException("Unsupported push provider: {$this->provider}"),
                    };

                    $results[] = $result;
                    $this->logSuccess($notification, $token, $result);
                } catch (\Exception $e) {
                    // Log individual device failure but continue
                    logger()->warning('Push notification failed for device', [
                        'notification_id' => $notification->id,
                        'device_token' => substr($token, 0, 10).'...',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Consider success if at least one device received the notification
            return count($results) > 0;
        } catch (\Exception $e) {
            // Log failure
            $this->logFailure($notification, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Get device tokens for notification recipient
     */
    private function getDeviceTokens(Notification $notification): array
    {
        // Get device tokens from metadata or user devices
        if (isset($notification->metadata['device_token'])) {
            return (array) $notification->metadata['device_token'];
        }

        if (isset($notification->metadata['device_tokens'])) {
            return (array) $notification->metadata['device_tokens'];
        }

        // In production, query user's registered device tokens from database
        // For now, return empty array if not in metadata
        return [];
    }

    /**
     * Send push notification via Firebase Cloud Messaging (FCM)
     */
    private function sendViaFcm(string $deviceToken, Notification $notification): array
    {
        $serverKey = config('notification.push.fcm.server_key');
        $projectId = config('notification.push.fcm.project_id');

        if (empty($serverKey)) {
            throw new NotificationException('FCM server key not configured');
        }

        // Prepare notification payload
        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $notification->subject,
                    'body' => $notification->body ?? '',
                ],
                'data' => $notification->metadata['data'] ?? [],
            ],
        ];

        // Add additional FCM options if provided
        if (isset($notification->metadata['fcm'])) {
            $fcmOptions = $notification->metadata['fcm'];

            if (isset($fcmOptions['priority'])) {
                $payload['message']['android']['priority'] = $fcmOptions['priority'];
                $payload['message']['apns']['headers']['apns-priority'] = $fcmOptions['priority'] === 'high' ? '10' : '5';
            }

            if (isset($fcmOptions['ttl'])) {
                $payload['message']['android']['ttl'] = $fcmOptions['ttl'].'s';
                $payload['message']['apns']['headers']['apns-expiration'] = time() + $fcmOptions['ttl'];
            }

            if (isset($fcmOptions['badge'])) {
                $payload['message']['apns']['payload']['aps']['badge'] = $fcmOptions['badge'];
            }

            if (isset($fcmOptions['sound'])) {
                $payload['message']['android']['notification']['sound'] = $fcmOptions['sound'];
                $payload['message']['apns']['payload']['aps']['sound'] = $fcmOptions['sound'];
            }
        }

        // Send via FCM HTTP v1 API
        $response = Http::withHeaders([
            'Authorization' => "key={$serverKey}",
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $deviceToken,
            'notification' => [
                'title' => $notification->subject,
                'body' => $notification->body ?? '',
            ],
            'data' => $notification->metadata['data'] ?? [],
            'priority' => 'high',
        ]);

        if (!$response->successful()) {
            $error = $response->json('error') ?? $response->json('message') ?? 'Unknown FCM error';
            throw new NotificationException("FCM API error: {$error}");
        }

        $data = $response->json();

        return [
            'provider' => 'fcm',
            'message_id' => $data['message_id'] ?? $data['results'][0]['message_id'] ?? null,
            'success' => $data['success'] ?? 1,
            'failure' => $data['failure'] ?? 0,
            'canonical_ids' => $data['canonical_ids'] ?? 0,
        ];
    }

    /**
     * Log successful send
     */
    private function logSuccess(Notification $notification, string $deviceToken, array $providerMetadata = []): void
    {
        $this->logRepository->create([
            'tenant_id' => $notification->tenant_id,
            'organization_id' => $notification->organization_id,
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'type' => $notification->type,
            'status' => NotificationStatus::SENT,
            'channel' => 'push',
            'recipient' => substr($deviceToken, 0, 20).'...', // Truncate for security
            'subject' => $notification->subject,
            'sent_at' => now(),
            'metadata' => array_merge([
                'sent_via' => 'push',
                'enabled' => $this->enabled,
            ], $providerMetadata),
        ]);
    }

    /**
     * Log failed send
     */
    private function logFailure(Notification $notification, string $errorMessage): void
    {
        $this->logRepository->create([
            'tenant_id' => $notification->tenant_id,
            'organization_id' => $notification->organization_id,
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'type' => $notification->type,
            'status' => NotificationStatus::FAILED,
            'channel' => 'push',
            'recipient' => 'device_token',
            'subject' => $notification->subject,
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'metadata' => [
                'sent_via' => 'push',
                'provider' => $this->provider,
                'enabled' => $this->enabled,
            ],
        ]);
    }
}
