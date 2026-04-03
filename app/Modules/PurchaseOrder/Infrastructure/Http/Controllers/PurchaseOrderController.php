<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\PurchaseOrder\Application\Contracts\ApprovePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CancelPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\DeletePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\FindPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\SubmitPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\Contracts\UpdatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Application\DTOs\PurchaseOrderData;
use Modules\PurchaseOrder\Application\DTOs\UpdatePurchaseOrderData;
use Modules\PurchaseOrder\Infrastructure\Http\Requests\StorePurchaseOrderRequest;
use Modules\PurchaseOrder\Infrastructure\Http\Requests\UpdatePurchaseOrderRequest;
use Modules\PurchaseOrder\Infrastructure\Http\Resources\PurchaseOrderCollection;
use Modules\PurchaseOrder\Infrastructure\Http\Resources\PurchaseOrderResource;

class PurchaseOrderController extends AuthorizedController
{
    public function __construct(
        protected FindPurchaseOrderServiceInterface $findService,
        protected CreatePurchaseOrderServiceInterface $createService,
        protected UpdatePurchaseOrderServiceInterface $updateService,
        protected DeletePurchaseOrderServiceInterface $deleteService,
        protected SubmitPurchaseOrderServiceInterface $submitService,
        protected ApprovePurchaseOrderServiceInterface $approveService,
        protected CancelPurchaseOrderServiceInterface $cancelService,
    ) {}

    public function index(Request $request): PurchaseOrderCollection
    {
        $filters = $request->only(['tenant_id', 'status', 'supplier_id', 'warehouse_id']);

        return new PurchaseOrderCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = PurchaseOrderData::fromArray([
            'tenantId'          => $v['tenant_id'],
            'referenceNumber'   => $v['reference_number'],
            'supplierId'        => $v['supplier_id'],
            'orderDate'         => $v['order_date'],
            'supplierReference' => $v['supplier_reference'] ?? null,
            'expectedDate'      => $v['expected_date'] ?? null,
            'warehouseId'       => $v['warehouse_id'] ?? null,
            'currency'          => $v['currency'] ?? 'USD',
            'subtotal'          => $v['subtotal'] ?? 0.0,
            'taxAmount'         => $v['tax_amount'] ?? 0.0,
            'discountAmount'    => $v['discount_amount'] ?? 0.0,
            'totalAmount'       => $v['total_amount'] ?? 0.0,
            'notes'             => $v['notes'] ?? null,
            'metadata'          => $v['metadata'] ?? null,
            'status'            => $v['status'] ?? 'draft',
        ]);

        $order = $this->createService->execute($dto->toArray());

        return (new PurchaseOrderResource($order))->response()->setStatusCode(201);
    }

    public function show(int $id): PurchaseOrderResource|JsonResponse
    {
        $order = $this->findService->find($id);

        if (! $order) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new PurchaseOrderResource($order);
    }

    public function update(UpdatePurchaseOrderRequest $request, int $id): PurchaseOrderResource
    {
        $v   = $request->validated();
        $dto = UpdatePurchaseOrderData::fromArray(array_merge(['id' => $id], [
            'supplierReference' => $v['supplier_reference'] ?? null,
            'expectedDate'      => $v['expected_date'] ?? null,
            'warehouseId'       => $v['warehouse_id'] ?? null,
            'notes'             => $v['notes'] ?? null,
            'metadata'          => $v['metadata'] ?? null,
        ]));

        return new PurchaseOrderResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Purchase order deleted successfully']);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $order = $this->submitService->execute([
            'id'           => $id,
            'submitted_by' => $request->integer('submitted_by'),
        ]);

        return (new PurchaseOrderResource($order))->response();
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $order = $this->approveService->execute([
            'id'          => $id,
            'approved_by' => $request->integer('approved_by'),
        ]);

        return (new PurchaseOrderResource($order))->response();
    }

    public function cancel(int $id): JsonResponse
    {
        $order = $this->cancelService->execute(['id' => $id]);

        return (new PurchaseOrderResource($order))->response();
    }
}
