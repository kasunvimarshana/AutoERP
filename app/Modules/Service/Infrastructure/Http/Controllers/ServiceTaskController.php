<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Service\Application\Contracts\CreateServiceTaskServiceInterface;
use Modules\Service\Application\Contracts\UpdateServiceTaskServiceInterface;
use Modules\Service\Domain\RepositoryInterfaces\ServiceTaskRepositoryInterface;
use Modules\Service\Infrastructure\Http\Requests\CreateServiceTaskRequest;
use Modules\Service\Infrastructure\Http\Requests\UpdateServiceTaskRequest;
use Modules\Service\Infrastructure\Http\Resources\ServiceTaskResource;

class ServiceTaskController extends AuthorizedController
{
    public function __construct(
        private readonly ServiceTaskRepositoryInterface $taskRepository,
        private readonly CreateServiceTaskServiceInterface $createService,
        private readonly UpdateServiceTaskServiceInterface $updateService,
    ) {}

    public function index(int $workOrderId): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $tasks = $this->taskRepository->findByWorkOrder($tenantId, $workOrderId);

        return ServiceTaskResource::collection($tasks);
    }

    public function show(int $workOrderId, int $id): ServiceTaskResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $task = $this->taskRepository->findById($tenantId, $id);

        abort_if($task === null || $task->getServiceWorkOrderId() !== $workOrderId, 404, 'Service task not found.');

        return new ServiceTaskResource($task);
    }

    public function store(CreateServiceTaskRequest $request, int $workOrderId): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'service_work_order_id' => $workOrderId,
            'changed_by' => $request->user()?->id,
        ]);

        return (new ServiceTaskResource($this->createService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateServiceTaskRequest $request, int $workOrderId, int $id): ServiceTaskResource
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $task = $this->taskRepository->findById($tenantId, $id);

        abort_if($task === null || $task->getServiceWorkOrderId() !== $workOrderId, 404, 'Service task not found.');

        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => $tenantId,
            'changed_by' => $request->user()?->id,
        ]);

        return new ServiceTaskResource($this->updateService->execute($data));
    }

    public function destroy(int $workOrderId, int $id): Response
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $task = $this->taskRepository->findById($tenantId, $id);

        abort_if($task === null || $task->getServiceWorkOrderId() !== $workOrderId, 404, 'Service task not found.');

        $this->taskRepository->delete($tenantId, $id);

        return response()->noContent();
    }
}
