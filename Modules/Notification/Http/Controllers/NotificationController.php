<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Notification\Http\Resources\NotificationResource;
use Modules\Notification\Models\Notification;
use Modules\Notification\Repositories\NotificationRepository;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {}

    /**
     * Display a listing of user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::query()
            ->where('user_id', $request->user()->id)
            ->with(['template', 'user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('is_read')) {
            if ($request->boolean('is_read')) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $perPage = $request->get('per_page', 15);
        $notifications = $query->latest()->paginate($perPage);

        return ApiResponse::paginated(
            $notifications->setCollection(
                $notifications->getCollection()->map(fn ($notification) => new NotificationResource($notification))
            ),
            'Notifications retrieved successfully'
        );
    }

    /**
     * Display the specified notification.
     */
    public function show(Notification $notification): JsonResponse
    {
        $this->authorize('view', $notification);

        $notification->load(['template', 'user']);

        return ApiResponse::success(
            new NotificationResource($notification),
            'Notification retrieved successfully'
        );
    }

    /**
     * Mark the specified notification as read.
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        DB::transaction(function () use ($notification) {
            $notification->markAsRead();
        });

        return ApiResponse::success(
            new NotificationResource($notification->fresh()),
            'Notification marked as read'
        );
    }

    /**
     * Mark all notifications as read for current user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = $this->notificationRepository->markAllAsReadByUser(
            $request->user()->id
        );

        return ApiResponse::success(
            ['updated_count' => $updated],
            'All notifications marked as read'
        );
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        DB::transaction(function () use ($notification) {
            $notification->delete();
        });

        return ApiResponse::success(
            null,
            'Notification deleted successfully'
        );
    }

    /**
     * Get unread notification count for current user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationRepository->countUnreadByUser(
            $request->user()->id
        );

        return ApiResponse::success(
            ['unread_count' => $count],
            'Unread count retrieved successfully'
        );
    }
}
