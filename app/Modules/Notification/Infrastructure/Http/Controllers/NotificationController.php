<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Application\Contracts\NotificationServiceInterface;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId  = (int) $request->get('tenant_id', 0);
        $userId    = (int) $request->get('user_id', 0);
        $unreadOnly = (bool) $request->get('unread_only', false);

        return response()->json(
            $this->service->listForUser($tenantId, $userId, $unreadOnly)
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->getById($id));
    }

    public function markRead(int $id): JsonResponse
    {
        return response()->json($this->service->markAsRead($id));
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $userId   = (int) $request->get('user_id', 0);

        $this->service->markAllRead($tenantId, $userId);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $userId   = (int) $request->get('user_id', 0);

        return response()->json(['count' => $this->service->countUnread($tenantId, $userId)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}
