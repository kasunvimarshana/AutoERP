<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Returns\Application\Contracts\PurchaseReturnServiceInterface;
use Modules\Returns\Infrastructure\Http\Resources\PurchaseReturnResource;

class PurchaseReturnController extends Controller
{
    public function __construct(
        private readonly PurchaseReturnServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $returns = $this->service->getAllPurchaseReturns($tenantId);

        return response()->json(PurchaseReturnResource::collection($returns));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->createPurchaseReturn($tenantId, $request->all());

        return response()->json(new PurchaseReturnResource($entity), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->getPurchaseReturn($tenantId, $id);

        return response()->json(new PurchaseReturnResource($entity));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $existing = $this->service->getPurchaseReturn($tenantId, $id);

        $updated = new \Modules\Returns\Domain\Entities\PurchaseReturn(
            id: $existing->id,
            tenantId: $existing->tenantId,
            purchaseOrderId: $request->input('purchase_order_id', $existing->purchaseOrderId),
            supplierId: (string) $request->input('supplier_id', $existing->supplierId),
            warehouseId: (string) $request->input('warehouse_id', $existing->warehouseId),
            reference: (string) $request->input('reference', $existing->reference),
            status: (string) $request->input('status', $existing->status),
            returnDate: $request->has('return_date')
                ? new \DateTimeImmutable($request->input('return_date'))
                : $existing->returnDate,
            reason: $request->input('reason', $existing->reason),
            totalAmount: (float) $request->input('total_amount', $existing->totalAmount),
            creditMemoNumber: $request->input('credit_memo_number', $existing->creditMemoNumber),
            refundAmount: (float) $request->input('refund_amount', $existing->refundAmount),
            notes: $request->input('notes', $existing->notes),
            createdAt: $existing->createdAt,
            updatedAt: now(),
        );

        /** @var \Modules\Returns\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface $repo */
        $repo = app(\Modules\Returns\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface::class);
        $repo->save($updated);

        return response()->json(new PurchaseReturnResource($updated));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        /** @var \Modules\Returns\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface $repo */
        $repo = app(\Modules\Returns\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface::class);
        $repo->delete($tenantId, $id);

        return response()->json(null, 204);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->approvePurchaseReturn($tenantId, $id);

        return response()->json(new PurchaseReturnResource($entity));
    }

    public function complete(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->completePurchaseReturn($tenantId, $id);

        return response()->json(new PurchaseReturnResource($entity));
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->cancelPurchaseReturn($tenantId, $id);

        return response()->json(new PurchaseReturnResource($entity));
    }
}
