<?php

declare(strict_types=1);

namespace Modules\Notification\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Notification\Application\Commands\DeleteNotificationCommand;
use Modules\Notification\Application\Commands\MarkNotificationReadCommand;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Services\NotificationService;
use Modules\Notification\Interfaces\Http\Requests\SendNotificationRequest;
use Modules\Notification\Interfaces\Http\Resources\NotificationResource;

class NotificationController extends BaseController
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $userId = (int) request('user_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->service->findAll($tenantId, $userId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($n) => (new NotificationResource($n))->resolve(),
                $result['items']
            ),
            message: 'Notifications retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $notification = $this->service->findById($id, $tenantId);

        if ($notification === null) {
            return $this->error('Notification not found', status: 404);
        }

        return $this->success(
            data: (new NotificationResource($notification))->resolve(),
            message: 'Notification retrieved successfully',
        );
    }

    public function send(SendNotificationRequest $request): JsonResponse
    {
        try {
            $notification = $this->service->sendNotification(new SendNotificationCommand(
                tenantId: $request->validated('tenant_id'),
                userId: $request->validated('user_id'),
                channel: $request->validated('channel'),
                eventType: $request->validated('event_type'),
                subject: $request->validated('subject'),
                body: $request->validated('body'),
                templateId: $request->validated('template_id'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new NotificationResource($notification))->resolve(),
            message: 'Notification sent successfully',
            status: 201,
        );
    }

    public function markRead(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $notification = $this->service->markRead(new MarkNotificationReadCommand($id, $tenantId));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }

        return $this->success(
            data: (new NotificationResource($notification))->resolve(),
            message: 'Notification marked as read',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->deleteNotification(new DeleteNotificationCommand($id, $tenantId));

            return $this->success(message: 'Notification deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    public function unread(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $userId = (int) request('user_id');

        $notifications = $this->service->findUnread($tenantId, $userId);

        return $this->success(
            data: array_map(
                fn ($n) => (new NotificationResource($n))->resolve(),
                $notifications
            ),
            message: 'Unread notifications retrieved successfully',
        );
    }
}
