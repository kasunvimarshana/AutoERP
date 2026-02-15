<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notification Controller
 * 
 * Handles user notifications API endpoints
 */
class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Get user notifications
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'integer|min:1|max:100',
            'unread_only' => 'boolean',
        ]);

        $limit = $request->input('limit', 50);
        $unreadOnly = $request->boolean('unread_only');

        $notifications = $this->notificationService->getNotifications(
            $request->user()->id,
            $limit,
            $unreadOnly
        );

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $result = $this->notificationService->markAsRead(
            $notificationId,
            $request->user()->id
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'boolean',
        ]);

        $user = $request->user();
        $user->notification_preferences = $request->input('preferences');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated',
            'data' => [
                'preferences' => $user->notification_preferences,
            ],
        ]);
    }

    /**
     * Get notification preferences
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $preferences = $request->user()->notification_preferences ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'preferences' => $preferences,
            ],
        ]);
    }
}
