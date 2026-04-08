<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\SalesOrder\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\DeleteSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\DeliverSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\FindSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\ShipSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPackingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\StartPickingSalesOrderServiceInterface;
use Modules\SalesOrder\Application\Contracts\UpdateSalesOrderServiceInterface;
use Modules\SalesOrder\Application\DTOs\SalesOrderData;
use Modules\SalesOrder\Application\DTOs\UpdateSalesOrderData;
use Modules\SalesOrder\Infrastructure\Http\Requests\StoreSalesOrderRequest;
use Modules\SalesOrder\Infrastructure\Http\Requests\UpdateSalesOrderRequest;
use Modules\SalesOrder\Infrastructure\Http\Resources\SalesOrderCollection;
use Modules\SalesOrder\Infrastructure\Http\Resources\SalesOrderResource;

class SalesOrderController extends AuthorizedController
{
    public function __construct(
        protected FindSalesOrderServiceInterface $findService,
        protected CreateSalesOrderServiceInterface $createService,
        protected UpdateSalesOrderServiceInterface $updateService,
        protected DeleteSalesOrderServiceInterface $deleteService,
        protected ConfirmSalesOrderServiceInterface $confirmService,
        protected CancelSalesOrderServiceInterface $cancelService,
        protected StartPickingSalesOrderServiceInterface $startPickingService,
        protected StartPackingSalesOrderServiceInterface $startPackingService,
        protected ShipSalesOrderServiceInterface $shipService,
        protected DeliverSalesOrderServiceInterface $deliverService,
    ) {}

    public function index(Request $request): SalesOrderCollection
    {
        $filters = $request->only(['tenant_id', 'status', 'customer_id', 'warehouse_id']);

        return new SalesOrderCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = SalesOrderData::fromArray([
            'tenantId'          => $v['tenant_id'],
            'referenceNumber'   => $v['reference_number'],
            'customerId'        => $v['customer_id'],
            'orderDate'         => $v['order_date'],
            'customerReference' => $v['customer_reference'] ?? null,
            'requiredDate'      => $v['required_date'] ?? null,
            'warehouseId'       => $v['warehouse_id'] ?? null,
            'currency'          => $v['currency'] ?? 'USD',
            'subtotal'          => $v['subtotal'] ?? 0.0,
            'taxAmount'         => $v['tax_amount'] ?? 0.0,
            'discountAmount'    => $v['discount_amount'] ?? 0.0,
            'totalAmount'       => $v['total_amount'] ?? 0.0,
            'shippingAddress'   => $v['shipping_address'] ?? null,
            'notes'             => $v['notes'] ?? null,
            'metadata'          => $v['metadata'] ?? null,
            'status'            => $v['status'] ?? 'draft',
        ]);

        $order = $this->createService->execute($dto->toArray());

        return (new SalesOrderResource($order))->response()->setStatusCode(201);
    }

    public function show(int $id): SalesOrderResource|JsonResponse
    {
        $order = $this->findService->find($id);

        if (! $order) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new SalesOrderResource($order);
    }

    public function update(UpdateSalesOrderRequest $request, int $id): SalesOrderResource
    {
        $v   = $request->validated();
        $dto = UpdateSalesOrderData::fromArray(array_merge(['id' => $id], [
            'customerReference' => $v['customer_reference'] ?? null,
            'requiredDate'      => $v['required_date'] ?? null,
            'warehouseId'       => $v['warehouse_id'] ?? null,
            'shippingAddress'   => $v['shipping_address'] ?? null,
            'notes'             => $v['notes'] ?? null,
            'metadata'          => $v['metadata'] ?? null,
        ]));

        return new SalesOrderResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Sales order deleted successfully']);
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        $order = $this->confirmService->execute([
            'id'           => $id,
            'confirmed_by' => $request->integer('confirmed_by'),
        ]);

        return (new SalesOrderResource($order))->response();
    }

    public function cancel(int $id): JsonResponse
    {
        $order = $this->cancelService->execute(['id' => $id]);

        return (new SalesOrderResource($order))->response();
    }

    public function startPicking(int $id): JsonResponse
    {
        $order = $this->startPickingService->execute(['id' => $id]);

        return (new SalesOrderResource($order))->response();
    }

    public function startPacking(int $id): JsonResponse
    {
        $order = $this->startPackingService->execute(['id' => $id]);

        return (new SalesOrderResource($order))->response();
    }

    public function ship(Request $request, int $id): JsonResponse
    {
        $order = $this->shipService->execute([
            'id'         => $id,
            'shipped_by' => $request->integer('shipped_by'),
        ]);

        return (new SalesOrderResource($order))->response();
    }

    public function deliver(int $id): JsonResponse
    {
        $order = $this->deliverService->execute(['id' => $id]);

        return (new SalesOrderResource($order))->response();
    }
}
