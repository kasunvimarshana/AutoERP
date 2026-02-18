<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\NotificationService;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="Notification management endpoints"
 * )
 */
class NotificationController extends BaseController
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="Get user notifications",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification list retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 20);

        $result = $this->notificationService->getNotifications(
            $request->user()->id,
            $page,
            $perPage
        );

        return $this->success($result);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/unread",
     *     summary="Get unread notifications",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of notifications to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unread notifications retrieved successfully"
     *     )
     * )
     */
    public function unread(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        
        $notifications = $this->notificationService->getUnreadNotifications(
            $request->user()->id,
            $limit
        );

        return $this->success([
            'data' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/count",
     *     summary="Get unread notification count",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Unread count retrieved successfully"
     *     )
     * )
     */
    public function count(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user()->id);

        return $this->success(['count' => $count]);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/statistics",
     *     summary="Get notification statistics",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully"
     *     )
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->notificationService->getStatistics($request->user()->id);

        return $this->success($stats);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/{id}/read",
     *     summary="Mark notification as read",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read"
     *     )
     * )
     */
    public function markAsRead(string $id): JsonResponse
    {
        $success = $this->notificationService->markAsRead($id);

        if ($success) {
            return $this->success(['message' => 'Notification marked as read']);
        }

        return $this->error('Notification not found', 404);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/read-multiple",
     *     summary="Mark multiple notifications as read",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="notification_ids",
     *                 type="array",
     *                 @OA\Items(type="string", format="uuid")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notifications marked as read"
     *     )
     * )
     */
    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'uuid',
        ]);

        $count = $this->notificationService->markMultipleAsRead(
            $request->input('notification_ids')
        );

        return $this->success([
            'message' => "Marked {$count} notifications as read",
            'count' => $count,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/read-all",
     *     summary="Mark all notifications as read",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read"
     *     )
     * )
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);

        return $this->success([
            'message' => "Marked all notifications as read",
            'count' => $count,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/notifications/{id}",
     *     summary="Delete a notification",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification deleted"
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $success = $this->notificationService->deleteNotification($id);

        if ($success) {
            return $this->success(['message' => 'Notification deleted']);
        }

        return $this->error('Notification not found', 404);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/preferences",
     *     summary="Get notification preferences",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Preferences retrieved successfully"
     *     )
     * )
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $preferences = $this->notificationService->getPreferences($request->user()->id);

        return $this->success(['data' => $preferences]);
    }

    /**
     * @OA\Put(
     *     path="/api/notifications/preferences",
     *     summary="Update notification preferences",
     *     tags={"Notifications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="notification_type", type="string"),
     *             @OA\Property(
     *                 property="channels",
     *                 type="object",
     *                 @OA\Property(property="email", type="boolean"),
     *                 @OA\Property(property="database", type="boolean"),
     *                 @OA\Property(property="broadcast", type="boolean"),
     *                 @OA\Property(property="push", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preference updated successfully"
     *     )
     * )
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'notification_type' => 'required|string',
            'channels' => 'required|array',
            'channels.email' => 'boolean',
            'channels.database' => 'boolean',
            'channels.broadcast' => 'boolean',
            'channels.push' => 'boolean',
        ]);

        $preference = $this->notificationService->updatePreference(
            $request->user()->id,
            $request->input('notification_type'),
            $request->input('channels')
        );

        return $this->success([
            'message' => 'Notification preference updated',
            'data' => $preference,
        ]);
    }
}
