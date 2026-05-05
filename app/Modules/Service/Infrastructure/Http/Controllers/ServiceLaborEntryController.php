<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Service\Application\Contracts\CreateServiceLaborEntryServiceInterface;
use Modules\Service\Application\Contracts\UpdateServiceLaborEntryServiceInterface;
use Modules\Service\Domain\RepositoryInterfaces\ServiceLaborEntryRepositoryInterface;
use Modules\Service\Infrastructure\Http\Requests\CreateServiceLaborEntryRequest;
use Modules\Service\Infrastructure\Http\Requests\UpdateServiceLaborEntryRequest;
use Modules\Service\Infrastructure\Http\Resources\ServiceLaborEntryResource;

class ServiceLaborEntryController extends AuthorizedController
{
    public function __construct(
        private readonly ServiceLaborEntryRepositoryInterface $laborEntryRepository,
        private readonly CreateServiceLaborEntryServiceInterface $createService,
        private readonly UpdateServiceLaborEntryServiceInterface $updateService,
    ) {}

    public function index(int $workOrderId): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $entries = $this->laborEntryRepository->findByWorkOrder($tenantId, $workOrderId);

        return ServiceLaborEntryResource::collection($entries);
    }

    public function show(int $workOrderId, int $id): ServiceLaborEntryResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $entry = $this->laborEntryRepository->findById($tenantId, $id);

        abort_if($entry === null || $entry->getServiceWorkOrderId() !== $workOrderId, 404, 'Labor entry not found.');

        return new ServiceLaborEntryResource($entry);
    }

    public function store(CreateServiceLaborEntryRequest $request, int $workOrderId): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'service_work_order_id' => $workOrderId,
            'changed_by' => $request->user()?->id,
        ]);

        return (new ServiceLaborEntryResource($this->createService->execute($data)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateServiceLaborEntryRequest $request, int $workOrderId, int $id): ServiceLaborEntryResource
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $entry = $this->laborEntryRepository->findById($tenantId, $id);

        abort_if($entry === null || $entry->getServiceWorkOrderId() !== $workOrderId, 404, 'Labor entry not found.');

        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => $tenantId,
            'changed_by' => $request->user()?->id,
        ]);

        return new ServiceLaborEntryResource($this->updateService->execute($data));
    }

    public function destroy(int $workOrderId, int $id): Response
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $entry = $this->laborEntryRepository->findById($tenantId, $id);

        abort_if($entry === null || $entry->getServiceWorkOrderId() !== $workOrderId, 404, 'Labor entry not found.');

        $this->laborEntryRepository->delete($tenantId, $id);

        return response()->noContent();
    }
}
