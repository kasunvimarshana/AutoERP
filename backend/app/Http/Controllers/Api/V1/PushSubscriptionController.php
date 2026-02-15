<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WebPush\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Push Subscription Controller
 *
 * Handles Web Push notification subscriptions
 */
class PushSubscriptionController extends Controller
{
    public function __construct(
        private readonly WebPushService $webPushService
    ) {}

    /**
     * Get VAPID public key for frontend
     */
    public function getPublicKey(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'public_key' => WebPushService::getPublicKey(),
            ],
        ]);
    }

    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'string|in:aesgcm,aes128gcm',
        ]);

        try {
            $subscription = $this->webPushService->subscribe(
                $request->user(),
                $request->only(['endpoint', 'keys', 'contentEncoding'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Successfully subscribed to push notifications',
                'data' => [
                    'subscription_id' => $subscription->id,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to push notifications', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to subscribe to push notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        try {
            $result = $this->webPushService->unsubscribe(
                $request->user(),
                $request->input('endpoint')
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Successfully unsubscribed from push notifications',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to unsubscribe from push notifications', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to unsubscribe',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test push notification
     */
    public function test(Request $request): JsonResponse
    {
        try {
            $result = $this->webPushService->sendToUser(
                $request->user(),
                [
                    'title' => 'Test Notification',
                    'body' => 'This is a test push notification from AutoERP',
                    'icon' => '/images/logo.png',
                    'badge' => '/images/badge.png',
                    'tag' => 'test-notification',
                    'data' => [
                        'url' => '/',
                        'timestamp' => now()->toIso8601String(),
                    ],
                ]
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success']
                    ? 'Test notification sent successfully'
                    : 'Failed to send test notification',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send test push notification', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
