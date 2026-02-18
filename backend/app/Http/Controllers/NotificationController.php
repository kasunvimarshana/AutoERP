<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Services\Notifications\WebPushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    public function __construct(
        private readonly WebPushNotificationService $webPushService
    ) {}

    /**
     * Subscribe to push notifications
     */
    public function subscribePush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string',
        ]);

        $subscription = $this->webPushService->subscribe(
            auth()->user(),
            $validated
        );

        return $this->success($subscription, 'Successfully subscribed to push notifications');
    }

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribePush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $success = $this->webPushService->unsubscribe(
            auth()->user(),
            $validated['endpoint']
        );

        if ($success) {
            return $this->success(null, 'Successfully unsubscribed from push notifications');
        }

        return $this->error('Subscription not found', 404);
    }

    /**
     * Get user's notification preferences
     */
    public function getPreferences(): JsonResponse
    {
        $preferences = NotificationPreference::where('user_id', auth()->id())
            ->get()
            ->groupBy('channel');

        return $this->success($preferences);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.channel' => 'required|string|in:web_push,email,sms',
            'preferences.*.event_type' => 'required|string',
            'preferences.*.enabled' => 'required|boolean',
        ]);

        foreach ($validated['preferences'] as $pref) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'channel' => $pref['channel'],
                    'event_type' => $pref['event_type'],
                ],
                [
                    'tenant_id' => auth()->user()->tenant_id,
                    'enabled' => $pref['enabled'],
                ]
            );
        }

        return $this->success(null, 'Preferences updated successfully');
    }

    /**
     * Get user's push subscriptions
     */
    public function getPushSubscriptions(): JsonResponse
    {
        $subscriptions = PushSubscription::where('user_id', auth()->id())->get();

        return $this->success($subscriptions);
    }

    /**
     * Send a test notification
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        $success = $this->webPushService->send(
            auth()->user(),
            'Test Notification',
            'This is a test notification from AutoERP',
            ['event_type' => 'test']
        );

        if ($success) {
            return $this->success(null, 'Test notification sent successfully');
        }

        return $this->error('Failed to send test notification. Please ensure you have an active subscription.', 500);
    }

    /**
     * Get user's notification history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);

        $notifications = auth()->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->success($notifications);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $id): JsonResponse
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return $this->error('Notification not found', 404);
        }

        $notification->markAsRead();

        return $this->success(null, 'Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return $this->success(null, 'All notifications marked as read');
    }

    /**
     * Delete notification
     */
    public function deleteNotification(string $id): JsonResponse
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return $this->error('Notification not found', 404);
        }

        $notification->delete();

        return $this->success(null, 'Notification deleted');
    }
}
