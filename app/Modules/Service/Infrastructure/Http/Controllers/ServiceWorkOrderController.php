<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Service\Application\Contracts\CancelServiceWorkOrderServiceInterface;
use Modules\Service\Application\Contracts\CompleteServiceWorkOrderServiceInterface;
use Modules\Service\Application\Contracts\CreateServiceWorkOrderServiceInterface;
use Modules\Service\Application\Contracts\UpdateServiceWorkOrderServiceInterface;
use Modules\Service\Domain\RepositoryInterfaces\ServiceWorkOrderRepositoryInterface;
use Modules\Service\Infrastructure\Http\Requests\CreateServiceWorkOrderRequest;
use Modules\Service\Infrastructure\Http\Requests\UpdateServiceWorkOrderRequest;
use Modules\Service\Infrastructure\Http\Resources\ServiceWorkOrderResource;

class ServiceWorkOrderController extends AuthorizedController
{
    public function __construct(
        private readonly ServiceWorkOrderRepositoryInterface $workOrderRepository,
        private readonly CreateServiceWorkOrderServiceInterface $createService,
        private readonly UpdateServiceWorkOrderServiceInterface $updateService,
        private readonly CompleteServiceWorkOrderServiceInterface $completeService,
        private readonly CancelServiceWorkOrderServiceInterface $cancelService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $orgUnitId = request()->integer('org_unit_id') ?: null;

        $workOrders = $this->workOrderRepository->findByTenant(
            $tenantId,
            $orgUnitId,
            request()->only(['status', 'asset_id', 'priority']),
        );

        return ServiceWorkOrderResource::collection($workOrders);
    }

    public function show(int $id): ServiceWorkOrderResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $workOrder = $this->workOrderRepository->findById($tenantId, $id);

        abort_if($workOrder === null, 404, 'Service work order not found.');

        return new ServiceWorkOrderResource($workOrder);
    }

    public function store(CreateServiceWorkOrderRequest $request): ServiceWorkOrderResource
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'changed_by' => $request->user()?->id,
        ]);

        return new ServiceWorkOrderResource($this->createService->execute($data));
    }

    public function update(UpdateServiceWorkOrderRequest $request, int $id): ServiceWorkOrderResource
    {
        $data = array_merge($request->validated(), [
            'id' => $id,
            'tenant_id' => (int) $request->header('X-Tenant-ID'),
            'changed_by' => $request->user()?->id,
        ]);

        return new ServiceWorkOrderResource($this->updateService->execute($data));
    }

    public function complete(int $id): ServiceWorkOrderResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');

        return new ServiceWorkOrderResource($this->completeService->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'completed_at' => request()->input('completed_at'),
            'meter_out' => request()->input('meter_out'),
            'resolution' => request()->input('resolution'),
            'labor_subtotal' => request()->input('labor_subtotal'),
            'parts_subtotal' => request()->input('parts_subtotal'),
            'other_subtotal' => request()->input('other_subtotal'),
            'tax_total' => request()->input('tax_total'),
            'grand_total' => request()->input('grand_total'),
            'notes' => request()->input('notes'),
            'changed_by' => request()->user()?->id,
        ]));
    }

    public function cancel(int $id): ServiceWorkOrderResource
    {
        $tenantId = (int) request()->header('X-Tenant-ID');

        return new ServiceWorkOrderResource($this->cancelService->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'notes' => request()->input('notes'),
            'changed_by' => request()->user()?->id,
        ]));
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request()->header('X-Tenant-ID');
        $this->workOrderRepository->delete($tenantId, $id);

        return response()->json(null, 204);
    }
}
