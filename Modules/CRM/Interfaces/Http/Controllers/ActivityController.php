<?php

declare(strict_types=1);

namespace Modules\Crm\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Crm\Application\Commands\DeleteActivityCommand;
use Modules\Crm\Application\Commands\LogActivityCommand;
use Modules\Crm\Application\Services\ActivityService;
use Modules\Crm\Interfaces\Http\Requests\LogActivityRequest;
use Modules\Crm\Interfaces\Http\Resources\ActivityResource;

class ActivityController extends BaseController
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->activityService->listActivities($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($activity) => (new ActivityResource($activity))->resolve(),
                $result['items']
            ),
            message: 'Activities retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(LogActivityRequest $request): JsonResponse
    {
        try {
            $activity = $this->activityService->logActivity(new LogActivityCommand(
                tenantId: (int) $request->validated('tenant_id'),
                type: $request->validated('type'),
                subject: $request->validated('subject'),
                description: $request->validated('description'),
                contactId: $request->validated('contact_id') ? (int) $request->validated('contact_id') : null,
                leadId: $request->validated('lead_id') ? (int) $request->validated('lead_id') : null,
                scheduledAt: $request->validated('scheduled_at'),
                completedAt: $request->validated('completed_at'),
            ));

            return $this->success(
                data: (new ActivityResource($activity))->resolve(),
                message: 'Activity logged successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $activity = $this->activityService->findActivityById($id, $tenantId);

        if ($activity === null) {
            return $this->error('Activity not found', status: 404);
        }

        return $this->success(
            data: (new ActivityResource($activity))->resolve(),
            message: 'Activity retrieved successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->activityService->deleteActivity(new DeleteActivityCommand($id, $tenantId));

            return $this->success(message: 'Activity deleted successfully');
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
