<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\DeletePurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\FindPurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\UpdatePurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\DTOs\PurchaseOrderLineData;
use Modules\PurchaseOrder\Application\DTOs\UpdatePurchaseOrderLineData;
use Modules\PurchaseOrder\Infrastructure\Http\Requests\StorePurchaseOrderLineRequest;
use Modules\PurchaseOrder\Infrastructure\Http\Requests\UpdatePurchaseOrderLineRequest;
use Modules\PurchaseOrder\Infrastructure\Http\Resources\PurchaseOrderLineCollection;
use Modules\PurchaseOrder\Infrastructure\Http\Resources\PurchaseOrderLineResource;

class PurchaseOrderLineController extends AuthorizedController
{
    public function __construct(
        protected FindPurchaseOrderLineServiceInterface $findService,
        protected CreatePurchaseOrderLineServiceInterface $createService,
        protected UpdatePurchaseOrderLineServiceInterface $updateService,
        protected DeletePurchaseOrderLineServiceInterface $deleteService,
    ) {}

    public function index(Request $request): PurchaseOrderLineCollection
    {
        $filters = $request->only(['tenant_id', 'purchase_order_id', 'status']);

        return new PurchaseOrderLineCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StorePurchaseOrderLineRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = PurchaseOrderLineData::fromArray([
            'tenantId'        => $v['tenant_id'],
            'purchaseOrderId' => $v['purchase_order_id'],
            'lineNumber'      => $v['line_number'],
            'productId'       => $v['product_id'],
            'quantityOrdered' => $v['quantity_ordered'],
            'unitPrice'       => $v['unit_price'],
            'variationId'     => $v['variation_id'] ?? null,
            'description'     => $v['description'] ?? null,
            'uomId'           => $v['uom_id'] ?? null,
            'discountPercent' => $v['discount_percent'] ?? 0.0,
            'taxPercent'      => $v['tax_percent'] ?? 0.0,
            'lineTotal'       => $v['line_total'] ?? 0.0,
            'expectedDate'    => $v['expected_date'] ?? null,
            'notes'           => $v['notes'] ?? null,
            'metadata'        => $v['metadata'] ?? null,
            'status'          => $v['status'] ?? 'open',
        ]);

        $line = $this->createService->execute($dto->toArray());

        return (new PurchaseOrderLineResource($line))->response()->setStatusCode(201);
    }

    public function show(int $id): PurchaseOrderLineResource|JsonResponse
    {
        $line = $this->findService->find($id);

        if (! $line) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new PurchaseOrderLineResource($line);
    }

    public function update(UpdatePurchaseOrderLineRequest $request, int $id): PurchaseOrderLineResource
    {
        $v   = $request->validated();
        $dto = UpdatePurchaseOrderLineData::fromArray(array_merge(['id' => $id], [
            'quantityOrdered' => $v['quantity_ordered'] ?? 0.0,
            'unitPrice'       => $v['unit_price'] ?? 0.0,
            'discountPercent' => $v['discount_percent'] ?? 0.0,
            'taxPercent'      => $v['tax_percent'] ?? 0.0,
            'lineTotal'       => $v['line_total'] ?? 0.0,
            'expectedDate'    => $v['expected_date'] ?? null,
            'notes'           => $v['notes'] ?? null,
            'metadata'        => $v['metadata'] ?? null,
        ]));

        return new PurchaseOrderLineResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Purchase order line deleted successfully']);
    }
}
