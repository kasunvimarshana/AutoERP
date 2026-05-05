<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Service\Application\Contracts\CreateServicePartServiceInterface;
use Modules\Service\Application\Contracts\UpdateServicePartServiceInterface;
use Modules\Service\Domain\RepositoryInterfaces\ServicePartRepositoryInterface;
use Modules\Service\Infrastructure\Http\Requests\CreateServicePartRequest;
use Modules\Service\Infrastructure\Http\Requests\UpdateServicePartRequest;
use Modules\Service\Infrastructure\Http\Resources\ServicePartResource;

class ServicePartController extends AuthorizedController
{
    public function __construct(
        private readonly ServicePartRepositoryInterface $partRepository,
        private readonly CreateServicePartServiceInterface $createService,
        private readonly UpdateServicePartServiceInterface $updateService,
    ) {}

    public function index(int $workOrderId): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $parts = $this->partRepository->findByWorkOrder($tenantId, $workOrderId);

        return ServicePartResource::collection($parts);
    }

    public function show(int $workOrderId, int $id): ServicePartResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $part = $this->partRepository->findById($tenantId, $id);

        abort_if($part === null || $part->getServiceWorkOrderId() !== $workOrderId, 404, 'Service part not found.');

        return new ServicePartResource($part);
    }

    public function store(CreateServicePartRequest $request, int $workOrderId): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'service_work_order_id' => $workOrderId,
            'changed_by' => $request->user()?->id,
        ]);

        return (new ServicePartResource($this->createService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateServicePartRequest $request, int $workOrderId, int $id): ServicePartResource
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $part = $this->partRepository->findById($tenantId, $id);

        abort_if($part === null || $part->getServiceWorkOrderId() !== $workOrderId, 404, 'Service part not found.');

        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => $tenantId,
            'changed_by' => $request->user()?->id,
        ]);

        return new ServicePartResource($this->updateService->execute($data));
    }

    public function destroy(int $workOrderId, int $id): Response
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $part = $this->partRepository->findById($tenantId, $id);

        abort_if($part === null || $part->getServiceWorkOrderId() !== $workOrderId, 404, 'Service part not found.');

        $this->partRepository->delete($tenantId, $id);

        return response()->noContent();
    }
}
