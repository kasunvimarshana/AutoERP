<?php

declare(strict_types=1);

namespace Modules\Notification\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Notification\Application\Commands\CreateNotificationTemplateCommand;
use Modules\Notification\Application\Commands\DeleteNotificationTemplateCommand;
use Modules\Notification\Application\Commands\UpdateNotificationTemplateCommand;
use Modules\Notification\Application\Services\NotificationTemplateService;
use Modules\Notification\Interfaces\Http\Requests\CreateNotificationTemplateRequest;
use Modules\Notification\Interfaces\Http\Requests\UpdateNotificationTemplateRequest;
use Modules\Notification\Interfaces\Http\Resources\NotificationTemplateResource;

class NotificationTemplateController extends BaseController
{
    public function __construct(
        private readonly NotificationTemplateService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->service->findAll($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($tpl) => (new NotificationTemplateResource($tpl))->resolve(),
                $result['items']
            ),
            message: 'Notification templates retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateNotificationTemplateRequest $request): JsonResponse
    {
        try {
            $template = $this->service->createTemplate(new CreateNotificationTemplateCommand(
                tenantId: $request->validated('tenant_id'),
                channel: $request->validated('channel'),
                eventType: $request->validated('event_type'),
                name: $request->validated('name'),
                subject: $request->validated('subject'),
                body: $request->validated('body'),
                isActive: (bool) ($request->validated('is_active') ?? true),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new NotificationTemplateResource($template))->resolve(),
            message: 'Notification template created successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $template = $this->service->findById($id, $tenantId);

        if ($template === null) {
            return $this->error('Notification template not found', status: 404);
        }

        return $this->success(
            data: (new NotificationTemplateResource($template))->resolve(),
            message: 'Notification template retrieved successfully',
        );
    }

    public function update(UpdateNotificationTemplateRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $existing = $this->service->findById($id, $tenantId);

        if ($existing === null) {
            return $this->error('Notification template not found', status: 404);
        }

        try {
            $template = $this->service->updateTemplate(new UpdateNotificationTemplateCommand(
                id: $id,
                tenantId: $tenantId,
                channel: $request->validated('channel') ?? $existing->channel->value,
                eventType: $request->validated('event_type') ?? $existing->eventType,
                name: $request->validated('name') ?? $existing->name,
                subject: $request->validated('subject') ?? $existing->subject,
                body: $request->validated('body') ?? $existing->body,
                isActive: $request->validated('is_active') !== null
                    ? (bool) $request->validated('is_active')
                    : $existing->isActive,
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new NotificationTemplateResource($template))->resolve(),
            message: 'Notification template updated successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->deleteTemplate(new DeleteNotificationTemplateCommand($id, $tenantId));

            return $this->success(message: 'Notification template deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
