<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Service\Application\Contracts\CreateServiceReturnServiceInterface;
use Modules\Service\Application\Contracts\UpdateServiceReturnServiceInterface;
use Modules\Service\Domain\RepositoryInterfaces\ServiceReturnRepositoryInterface;
use Modules\Service\Infrastructure\Http\Requests\CreateServiceReturnRequest;
use Modules\Service\Infrastructure\Http\Requests\UpdateServiceReturnRequest;
use Modules\Service\Infrastructure\Http\Resources\ServiceReturnResource;

class ServiceReturnController extends AuthorizedController
{
    public function __construct(
        private readonly ServiceReturnRepositoryInterface $returnRepository,
        private readonly CreateServiceReturnServiceInterface $createService,
        private readonly UpdateServiceReturnServiceInterface $updateService,
    ) {}

    public function index(int $workOrderId): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $returns = $this->returnRepository->findByWorkOrder($tenantId, $workOrderId);

        return ServiceReturnResource::collection($returns);
    }

    public function show(int $workOrderId, int $id): ServiceReturnResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $return = $this->returnRepository->findById($tenantId, $id);

        abort_if($return === null || $return->getServiceWorkOrderId() !== $workOrderId, 404, 'Service return not found.');

        return new ServiceReturnResource($return);
    }

    public function store(CreateServiceReturnRequest $request, int $workOrderId): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'service_work_order_id' => $workOrderId,
            'changed_by' => $request->user()?->id,
        ]);

        return (new ServiceReturnResource($this->createService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateServiceReturnRequest $request, int $workOrderId, int $id): ServiceReturnResource
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $return = $this->returnRepository->findById($tenantId, $id);

        abort_if($return === null || $return->getServiceWorkOrderId() !== $workOrderId, 404, 'Service return not found.');

        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => $tenantId,
            'changed_by' => $request->user()?->id,
        ]);

        return new ServiceReturnResource($this->updateService->execute($data));
    }

    public function approve(int $workOrderId, int $id): ServiceReturnResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $return = $this->returnRepository->findById($tenantId, $id);

        abort_if($return === null || $return->getServiceWorkOrderId() !== $workOrderId, 404, 'Service return not found.');

        $data = [
            'id' => $id,
            'tenant_id' => $tenantId,
            'status' => 'approved',
        ];

        return new ServiceReturnResource($this->updateService->execute($data));
    }
}
