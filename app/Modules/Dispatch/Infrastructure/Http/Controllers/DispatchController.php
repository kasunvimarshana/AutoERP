<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Dispatch\Application\Contracts\CancelDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\ConfirmDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\CreateDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\DeleteDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\DeliverDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\FindDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\ShipDispatchServiceInterface;
use Modules\Dispatch\Application\Contracts\UpdateDispatchServiceInterface;
use Modules\Dispatch\Application\DTOs\DispatchData;
use Modules\Dispatch\Application\DTOs\UpdateDispatchData;
use Modules\Dispatch\Infrastructure\Http\Requests\StoreDispatchRequest;
use Modules\Dispatch\Infrastructure\Http\Requests\UpdateDispatchRequest;
use Modules\Dispatch\Infrastructure\Http\Resources\DispatchCollection;
use Modules\Dispatch\Infrastructure\Http\Resources\DispatchResource;

class DispatchController extends AuthorizedController
{
    public function __construct(
        protected FindDispatchServiceInterface $findService,
        protected CreateDispatchServiceInterface $createService,
        protected UpdateDispatchServiceInterface $updateService,
        protected DeleteDispatchServiceInterface $deleteService,
        protected ConfirmDispatchServiceInterface $confirmService,
        protected ShipDispatchServiceInterface $shipService,
        protected DeliverDispatchServiceInterface $deliverService,
        protected CancelDispatchServiceInterface $cancelService,
    ) {}

    public function index(Request $request): DispatchCollection
    {
        $filters = $request->only(['tenant_id', 'status', 'warehouse_id', 'sales_order_id', 'customer_id']);

        return new DispatchCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreDispatchRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = DispatchData::fromArray([
            'tenantId'             => $v['tenant_id'],
            'referenceNumber'      => $v['reference_number'],
            'warehouseId'          => $v['warehouse_id'],
            'customerId'           => $v['customer_id'],
            'dispatchDate'         => $v['dispatch_date'],
            'salesOrderId'         => $v['sales_order_id'] ?? null,
            'customerReference'    => $v['customer_reference'] ?? null,
            'estimatedDeliveryDate'=> $v['estimated_delivery_date'] ?? null,
            'carrier'              => $v['carrier'] ?? null,
            'notes'                => $v['notes'] ?? null,
            'metadata'             => $v['metadata'] ?? null,
            'status'               => $v['status'] ?? 'draft',
            'currency'             => $v['currency'] ?? 'USD',
            'totalWeight'          => $v['total_weight'] ?? null,
        ]);

        $dispatch = $this->createService->execute($dto->toArray());

        return (new DispatchResource($dispatch))->response()->setStatusCode(201);
    }

    public function show(int $id): DispatchResource|JsonResponse
    {
        $dispatch = $this->findService->find($id);
        if (! $dispatch) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new DispatchResource($dispatch);
    }

    public function update(UpdateDispatchRequest $request, int $id): DispatchResource
    {
        $v   = $request->validated();
        $dto = UpdateDispatchData::fromArray([
            'id'                   => $id,
            'customerReference'    => $v['customer_reference'] ?? null,
            'estimatedDeliveryDate'=> $v['estimated_delivery_date'] ?? null,
            'carrier'              => $v['carrier'] ?? null,
            'trackingNumber'       => $v['tracking_number'] ?? null,
            'notes'                => $v['notes'] ?? null,
            'metadata'             => $v['metadata'] ?? null,
            'totalWeight'          => $v['total_weight'] ?? null,
        ]);

        return new DispatchResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Dispatch deleted successfully']);
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        $dispatch = $this->confirmService->execute([
            'id'           => $id,
            'confirmed_by' => $request->integer('confirmed_by'),
        ]);

        return (new DispatchResource($dispatch))->response();
    }

    public function ship(Request $request, int $id): JsonResponse
    {
        $dispatch = $this->shipService->execute([
            'id'              => $id,
            'shipped_by'      => $request->integer('shipped_by'),
            'tracking_number' => $request->input('tracking_number'),
        ]);

        return (new DispatchResource($dispatch))->response();
    }

    public function deliver(Request $request, int $id): JsonResponse
    {
        $dispatch = $this->deliverService->execute([
            'id'                   => $id,
            'actual_delivery_date' => $request->input('actual_delivery_date'),
        ]);

        return (new DispatchResource($dispatch))->response();
    }

    public function cancel(int $id): JsonResponse
    {
        $dispatch = $this->cancelService->execute(['id' => $id]);

        return (new DispatchResource($dispatch))->response();
    }
}
