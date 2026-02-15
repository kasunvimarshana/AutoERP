<?php

declare(strict_types=1);

namespace App\Services\WebPush;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Web Push Service
 * 
 * Handles native browser push notifications using Web Push API
 * No third-party services - uses VAPID keys for authentication
 */
class WebPushService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ]);
    }

    /**
     * Subscribe user to push notifications
     */
    public function subscribe(User $user, array $subscriptionData): PushSubscription
    {
        // Validate subscription data
        if (!isset($subscriptionData['endpoint']) || !isset($subscriptionData['keys'])) {
            throw new \InvalidArgumentException('Invalid subscription data');
        }

        // Check if subscription already exists
        $existing = PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $subscriptionData['endpoint'])
            ->first();

        if ($existing) {
            // Update existing subscription
            $existing->update([
                'public_key' => $subscriptionData['keys']['p256dh'] ?? null,
                'auth_token' => $subscriptionData['keys']['auth'] ?? null,
                'content_encoding' => $subscriptionData['contentEncoding'] ?? 'aesgcm',
            ]);
            return $existing;
        }

        // Create new subscription
        return PushSubscription::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'endpoint' => $subscriptionData['endpoint'],
            'public_key' => $subscriptionData['keys']['p256dh'] ?? null,
            'auth_token' => $subscriptionData['keys']['auth'] ?? null,
            'content_encoding' => $subscriptionData['contentEncoding'] ?? 'aesgcm',
        ]);
    }

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe(User $user, string $endpoint): bool
    {
        return PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $endpoint)
            ->delete() > 0;
    }

    /**
     * Send push notification to user
     */
    public function sendToUser(User $user, array $payload): array
    {
        $subscriptions = $user->pushSubscriptions;

        if ($subscriptions->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No push subscriptions found for user',
            ];
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $result = $this->sendToSubscription($subscription, $payload);
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                    
                    // If subscription is expired/invalid, remove it
                    if ($result['expired']) {
                        $subscription->delete();
                    }
                }
                
                $results[] = $result;
            } catch (\Exception $e) {
                Log::error('Failed to send push notification', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
                
                $failureCount++;
                $results[] = [
                    'success' => false,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => $successCount > 0,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
        ];
    }

    /**
     * Send push notification to specific subscription
     */
    public function sendToSubscription(PushSubscription $subscription, array $payload): array
    {
        try {
            $webPushSubscription = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding,
            ]);

            // Send notification
            $result = $this->webPush->sendOneNotification(
                $webPushSubscription,
                json_encode($payload)
            );

            // Check if successful
            if ($result->isSuccess()) {
                return [
                    'success' => true,
                    'subscription_id' => $subscription->id,
                ];
            }

            // Handle failure
            $expired = $result->isSubscriptionExpired();
            
            Log::warning('Push notification failed', [
                'subscription_id' => $subscription->id,
                'expired' => $expired,
                'status_code' => $result->getResponse()?->getStatusCode(),
            ]);

            return [
                'success' => false,
                'subscription_id' => $subscription->id,
                'expired' => $expired,
                'status_code' => $result->getResponse()?->getStatusCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Exception sending push notification', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send push notification to multiple users
     */
    public function sendToUsers(array $userIds, array $payload): array
    {
        $users = User::with('pushSubscriptions')
            ->whereIn('id', $userIds)
            ->get();

        $results = [];
        $totalSuccess = 0;
        $totalFailure = 0;

        foreach ($users as $user) {
            $result = $this->sendToUser($user, $payload);
            $totalSuccess += $result['success_count'];
            $totalFailure += $result['failure_count'];
            $results[] = [
                'user_id' => $user->id,
                'result' => $result,
            ];
        }

        return [
            'success' => $totalSuccess > 0,
            'total_success' => $totalSuccess,
            'total_failure' => $totalFailure,
            'results' => $results,
        ];
    }

    /**
     * Get VAPID public key for frontend
     */
    public static function getPublicKey(): string
    {
        return config('webpush.vapid.public_key');
    }

    /**
     * Generate VAPID keys (run once during setup)
     */
    public static function generateVapidKeys(): array
    {
        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
        
        return [
            'publicKey' => $keys['publicKey'],
            'privateKey' => $keys['privateKey'],
        ];
    }
}
