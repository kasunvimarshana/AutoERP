<?php
namespace Modules\Notification\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Notification\Domain\Contracts\NotificationRepositoryInterface;
use Modules\Shared\Application\ResponseFormatter;
class NotificationController extends Controller
{
    public function __construct(private NotificationRepositoryInterface $repo) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginateForUser((string) auth()->id()));
    }

    public function show(string $id): JsonResponse
    {
        $notification = $this->repo->findById($id);
        if (! $notification) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($notification);
    }

    public function markRead(string $id): JsonResponse
    {
        $this->repo->markRead($id);
        return ResponseFormatter::success(null, 'Marked as read.');
    }

    public function markAllRead(): JsonResponse
    {
        $this->repo->markAllReadForUser((string) auth()->id());
        return ResponseFormatter::success(null, 'All marked as read.');
    }

    public function unreadCount(): JsonResponse
    {
        $count = $this->repo->countUnreadForUser((string) auth()->id());
        return ResponseFormatter::success(['unread_count' => $count]);
    }

    public function destroy(string $id): JsonResponse
    {
        $notification = $this->repo->findByIdAndUser($id, (string) auth()->id());
        if (! $notification) {
            return ResponseFormatter::error('Notification not found.', [], 404);
        }
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Notification deleted.');
    }
}
