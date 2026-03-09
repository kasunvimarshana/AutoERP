<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\NotificationServiceInterface;
use App\Domain\Entities\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notification Controller
 *
 * Handles sending and querying notifications.
 */
class NotificationController extends Controller
{
    public function __construct(
        protected readonly NotificationServiceInterface $notificationService
    ) {}

    /**
     * Send a notification.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'event' => ['required', 'string'],
            'payload' => ['required', 'array'],
            'channel' => ['sometimes', 'string', 'in:email,webhook,sms,push'],
        ]);

        $success = $this->notificationService->send(
            $request->string('event')->toString(),
            $request->input('payload'),
            $request->only(['channel'])
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notification sent.' : 'Notification failed.',
        ]);
    }

    /**
     * List notification history for a tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $filters = $request->only(['event', 'status', 'per_page', 'page']);

        $query = Notification::where('tenant_id', $tenantId)
            ->when(isset($filters['event']), fn ($q) => $q->where('event', $filters['event']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->orderBy('created_at', 'desc');

        if (isset($filters['per_page'])) {
            $result = $query->paginate(
                (int) $filters['per_page'],
                ['*'],
                'page',
                (int) ($filters['page'] ?? 1)
            );

            return response()->json([
                'success' => true,
                'data' => $result->items(),
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                ],
            ]);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }
}
