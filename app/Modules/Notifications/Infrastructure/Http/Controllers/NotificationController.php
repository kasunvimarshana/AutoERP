<?php

declare(strict_types=1);

namespace Modules\Notifications\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notifications\Application\Contracts\NotificationServiceInterface;
use Modules\Notifications\Application\DTOs\CreateNotificationDTO;
use Modules\Notifications\Domain\Exceptions\NotificationNotFoundException;
use Modules\Notifications\Domain\ValueObjects\EntityType;
use Modules\Notifications\Domain\ValueObjects\NotificationChannel;
use Modules\Notifications\Domain\ValueObjects\NotificationType;
use Modules\Notifications\Domain\ValueObjects\RecipientType;
use Modules\Notifications\Infrastructure\Http\Requests\CreateNotificationRequest;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId  = $request->header('X-Tenant-ID');
        $orgUnitId = $request->query('org_unit_id', $tenantId);

        $notifications = $this->service->listByTenant($tenantId, $orgUnitId);

        return response()->json(['data' => array_map(
            fn ($n) => $this->transform($n),
            $notifications
        )]);
    }

    public function unread(Request $request): JsonResponse
    {
        $tenantId  = $request->header('X-Tenant-ID');
        $orgUnitId = $request->query('org_unit_id', $tenantId);

        $notifications = $this->service->listUnread($tenantId, $orgUnitId);

        return response()->json(['data' => array_map(
            fn ($n) => $this->transform($n),
            $notifications
        )]);
    }

    public function byEntity(Request $request, string $entityType, string $entityId): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');

        $notifications = $this->service->listByEntity($tenantId, $entityType, $entityId);

        return response()->json(['data' => array_map(
            fn ($n) => $this->transform($n),
            $notifications
        )]);
    }

    public function store(CreateNotificationRequest $request): JsonResponse
    {
        $tenantId  = $request->header('X-Tenant-ID');
        $orgUnitId = $request->input('org_unit_id', $tenantId);
        $data      = $request->validated();

        $dto = new CreateNotificationDTO(
            tenantId:           $tenantId,
            orgUnitId:          $orgUnitId,
            notificationNumber: $data['notification_number'],
            notificationType:   NotificationType::from($data['notification_type']),
            entityType:         EntityType::from($data['entity_type']),
            entityId:           $data['entity_id'] ?? null,
            recipientType:      RecipientType::from($data['recipient_type']),
            recipientId:        $data['recipient_id'] ?? null,
            title:              $data['title'],
            message:            $data['message'],
            channel:            NotificationChannel::from($data['channel']),
            metadata:           $data['metadata'] ?? null,
        );

        $notification = $this->service->create($dto);

        return response()->json(['data' => $this->transform($notification)], 201);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $notification = $this->service->getById($id);

            return response()->json(['data' => $this->transform($notification)]);
        } catch (NotificationNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function markRead(string $id): JsonResponse
    {
        try {
            $notification = $this->service->markRead($id);

            return response()->json(['data' => $this->transform($notification)]);
        } catch (NotificationNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json(null, 204);
        } catch (NotificationNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    private function transform(mixed $n): array
    {
        return [
            'id'                  => $n->id,
            'tenant_id'           => $n->tenantId,
            'org_unit_id'         => $n->orgUnitId,
            'row_version'         => $n->rowVersion,
            'notification_number' => $n->notificationNumber,
            'notification_type'   => $n->notificationType->value,
            'entity_type'         => $n->entityType->value,
            'entity_id'           => $n->entityId,
            'recipient_type'      => $n->recipientType->value,
            'recipient_id'        => $n->recipientId,
            'title'               => $n->title,
            'message'             => $n->message,
            'channel'             => $n->channel->value,
            'status'              => $n->status->value,
            'sent_at'             => $n->sentAt?->format('Y-m-d H:i:s'),
            'read_at'             => $n->readAt?->format('Y-m-d H:i:s'),
            'failed_reason'       => $n->failedReason,
            'metadata'            => $n->metadata,
            'is_active'           => $n->isActive,
            'created_at'          => $n->createdAt->format('Y-m-d H:i:s'),
            'updated_at'          => $n->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
