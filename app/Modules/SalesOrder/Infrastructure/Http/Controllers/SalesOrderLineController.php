<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\Contracts\DeleteSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\Contracts\FindSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\Contracts\UpdateSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\DTOs\SalesOrderLineData;
use Modules\SalesOrder\Application\DTOs\UpdateSalesOrderLineData;
use Modules\SalesOrder\Infrastructure\Http\Requests\StoreSalesOrderLineRequest;
use Modules\SalesOrder\Infrastructure\Http\Requests\UpdateSalesOrderLineRequest;
use Modules\SalesOrder\Infrastructure\Http\Resources\SalesOrderLineCollection;
use Modules\SalesOrder\Infrastructure\Http\Resources\SalesOrderLineResource;

class SalesOrderLineController extends AuthorizedController
{
    public function __construct(
        protected FindSalesOrderLineServiceInterface $findService,
        protected CreateSalesOrderLineServiceInterface $createService,
        protected UpdateSalesOrderLineServiceInterface $updateService,
        protected DeleteSalesOrderLineServiceInterface $deleteService,
    ) {}

    public function index(Request $request): SalesOrderLineCollection
    {
        $filters = $request->only(['tenant_id', 'sales_order_id', 'status']);

        return new SalesOrderLineCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreSalesOrderLineRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = SalesOrderLineData::fromArray([
            'tenantId'           => $v['tenant_id'],
            'salesOrderId'       => $v['sales_order_id'],
            'productId'          => $v['product_id'],
            'quantity'           => $v['quantity'],
            'unitPrice'          => $v['unit_price'],
            'productVariantId'   => $v['product_variant_id'] ?? null,
            'description'        => $v['description'] ?? null,
            'taxRate'            => $v['tax_rate'] ?? 0.0,
            'discountAmount'     => $v['discount_amount'] ?? 0.0,
            'totalAmount'        => $v['total_amount'] ?? 0.0,
            'unitOfMeasure'      => $v['unit_of_measure'] ?? null,
            'status'             => $v['status'] ?? 'pending',
            'warehouseLocationId' => $v['warehouse_location_id'] ?? null,
            'batchNumber'        => $v['batch_number'] ?? null,
            'serialNumber'       => $v['serial_number'] ?? null,
            'notes'              => $v['notes'] ?? null,
            'metadata'           => $v['metadata'] ?? null,
        ]);

        $line = $this->createService->execute($dto->toArray());

        return (new SalesOrderLineResource($line))->response()->setStatusCode(201);
    }

    public function show(int $id): SalesOrderLineResource|JsonResponse
    {
        $line = $this->findService->find($id);

        if (! $line) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new SalesOrderLineResource($line);
    }

    public function update(UpdateSalesOrderLineRequest $request, int $id): SalesOrderLineResource
    {
        $v   = $request->validated();
        $dto = UpdateSalesOrderLineData::fromArray(array_merge(['id' => $id], [
            'quantity'           => $v['quantity'] ?? 0.0,
            'unitPrice'          => $v['unit_price'] ?? 0.0,
            'taxRate'            => $v['tax_rate'] ?? 0.0,
            'discountAmount'     => $v['discount_amount'] ?? 0.0,
            'totalAmount'        => $v['total_amount'] ?? 0.0,
            'warehouseLocationId' => $v['warehouse_location_id'] ?? null,
            'batchNumber'        => $v['batch_number'] ?? null,
            'serialNumber'       => $v['serial_number'] ?? null,
            'description'        => $v['description'] ?? null,
            'notes'              => $v['notes'] ?? null,
            'metadata'           => $v['metadata'] ?? null,
        ]));

        return new SalesOrderLineResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Sales order line deleted successfully']);
    }
}
