<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Service\Application\Contracts\CreateServiceWarrantyClaimServiceInterface;
use Modules\Service\Application\Contracts\UpdateServiceWarrantyClaimServiceInterface;
use Modules\Service\Domain\RepositoryInterfaces\ServiceWarrantyClaimRepositoryInterface;
use Modules\Service\Infrastructure\Http\Requests\CreateServiceWarrantyClaimRequest;
use Modules\Service\Infrastructure\Http\Requests\UpdateServiceWarrantyClaimRequest;
use Modules\Service\Infrastructure\Http\Resources\ServiceWarrantyClaimResource;

class ServiceWarrantyClaimController extends AuthorizedController
{
    public function __construct(
        private readonly ServiceWarrantyClaimRepositoryInterface $claimRepository,
        private readonly CreateServiceWarrantyClaimServiceInterface $createService,
        private readonly UpdateServiceWarrantyClaimServiceInterface $updateService,
    ) {}

    public function index(int $workOrderId): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $claims = $this->claimRepository->findByWorkOrder($tenantId, $workOrderId);

        return ServiceWarrantyClaimResource::collection($claims);
    }

    public function show(int $workOrderId, int $id): ServiceWarrantyClaimResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $claim = $this->claimRepository->findById($tenantId, $id);

        abort_if($claim === null || $claim->getServiceWorkOrderId() !== $workOrderId, 404, 'Warranty claim not found.');

        return new ServiceWarrantyClaimResource($claim);
    }

    public function store(CreateServiceWarrantyClaimRequest $request, int $workOrderId): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'service_work_order_id' => $workOrderId,
            'changed_by' => $request->user()?->id,
        ]);

        return (new ServiceWarrantyClaimResource($this->createService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateServiceWarrantyClaimRequest $request, int $workOrderId, int $id): ServiceWarrantyClaimResource
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $claim = $this->claimRepository->findById($tenantId, $id);

        abort_if($claim === null || $claim->getServiceWorkOrderId() !== $workOrderId, 404, 'Warranty claim not found.');

        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => $tenantId,
            'changed_by' => $request->user()?->id,
        ]);

        return new ServiceWarrantyClaimResource($this->updateService->execute($data));
    }

    public function submit(int $workOrderId, int $id): ServiceWarrantyClaimResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $claim = $this->claimRepository->findById($tenantId, $id);

        abort_if($claim === null || $claim->getServiceWorkOrderId() !== $workOrderId, 404, 'Warranty claim not found.');

        $data = [
            'id' => $id,
            'tenant_id' => $tenantId,
            'status' => 'submitted',
        ];

        return new ServiceWarrantyClaimResource($this->updateService->execute($data));
    }
}
