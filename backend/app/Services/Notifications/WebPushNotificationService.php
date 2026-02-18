<?php

namespace App\Services\Notifications;

use App\Models\NotificationPreference;
use App\Models\NotificationQueue;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class WebPushNotificationService
{
    /**
     * Send a push notification to a user
     */
    public function send(User $user, string $title, string $body, array $data = []): bool
    {
        // Check if user has enabled web push notifications
        if (! $this->isChannelEnabled($user->id, $data['event_type'] ?? 'general')) {
            Log::info('Web push disabled for user', ['user_id' => $user->id]);

            return false;
        }

        // Get user's push subscriptions
        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            Log::info('No push subscriptions found', ['user_id' => $user->id]);

            return false;
        }

        $payload = [
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'icon' => config('app.url').'/images/logo.png',
            'badge' => config('app.url').'/images/badge.png',
            'timestamp' => now()->timestamp,
        ];

        $success = false;
        foreach ($subscriptions as $subscription) {
            try {
                // In a real implementation, this would use a library like minishlink/web-push
                // For now, we'll queue it for processing
                $this->queueNotification($user, 'web_push', $data['event_type'] ?? 'general', $payload);
                $success = true;
            } catch (\Exception $e) {
                Log::error('Failed to send push notification', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $success;
    }

    /**
     * Send notification to multiple users
     */
    public function sendToMany(array $userIds, string $title, string $body, array $data = []): void
    {
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $this->send($user, $title, $body, $data);
            }
        }
    }

    /**
     * Subscribe user to push notifications
     */
    public function subscribe(User $user, array $subscriptionData): PushSubscription
    {
        return PushSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'endpoint' => $subscriptionData['endpoint'],
            ],
            [
                'tenant_id' => $user->tenant_id,
                'public_key' => $subscriptionData['keys']['p256dh'] ?? null,
                'auth_token' => $subscriptionData['keys']['auth'] ?? null,
                'content_encoding' => $subscriptionData['contentEncoding'] ?? 'aesgcm',
            ]
        );
    }

    /**
     * Unsubscribe user from push notifications
     */
    public function unsubscribe(User $user, string $endpoint): bool
    {
        return PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $endpoint)
            ->delete() > 0;
    }

    /**
     * Check if channel is enabled for user
     */
    private function isChannelEnabled(int $userId, string $eventType): bool
    {
        return NotificationPreference::isEnabled($userId, 'web_push', $eventType);
    }

    /**
     * Queue notification for later processing
     */
    private function queueNotification(User $user, string $channel, string $type, array $data): void
    {
        NotificationQueue::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'channel' => $channel,
            'type' => $type,
            'data' => $data,
            'status' => 'pending',
            'scheduled_at' => now(),
        ]);
    }

    /**
     * Process pending notifications
     */
    public function processPending(int $limit = 100): void
    {
        $notifications = NotificationQueue::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        foreach ($notifications as $notification) {
            try {
                if ($notification->canRetry()) {
                    // Here you would actually send the push notification
                    // using a library like minishlink/web-push
                    $this->sendPushNotification($notification);
                    $notification->markAsSent();
                }
            } catch (\Exception $e) {
                $notification->markAsFailed($e->getMessage());
                Log::error('Failed to process notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Actually send the push notification (stub for real implementation)
     */
    private function sendPushNotification(NotificationQueue $notification): void
    {
        // This is where you would integrate with a real Web Push library
        // For example, using minishlink/web-push:
        //
        // $webPush = new WebPush($auth);
        // $webPush->sendNotification($subscription, json_encode($notification->data));

        Log::info('Push notification sent', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'type' => $notification->type,
        ]);
    }
}
