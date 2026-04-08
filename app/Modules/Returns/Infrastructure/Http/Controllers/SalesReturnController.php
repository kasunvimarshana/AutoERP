<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Returns\Application\Contracts\SalesReturnServiceInterface;
use Modules\Returns\Infrastructure\Http\Resources\SalesReturnResource;

class SalesReturnController extends Controller
{
    public function __construct(
        private readonly SalesReturnServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $returns = $this->service->getAllSalesReturns($tenantId);

        return response()->json(SalesReturnResource::collection($returns));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->createSalesReturn($tenantId, $request->all());

        return response()->json(new SalesReturnResource($entity), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->getSalesReturn($tenantId, $id);

        return response()->json(new SalesReturnResource($entity));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $existing = $this->service->getSalesReturn($tenantId, $id);

        $updated = new \Modules\Returns\Domain\Entities\SalesReturn(
            id: $existing->id,
            tenantId: $existing->tenantId,
            salesOrderId: $request->input('sales_order_id', $existing->salesOrderId),
            customerId: (string) $request->input('customer_id', $existing->customerId),
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
            restockingFee: (float) $request->input('restocking_fee', $existing->restockingFee),
            notes: $request->input('notes', $existing->notes),
            createdAt: $existing->createdAt,
            updatedAt: now(),
        );

        /** @var \Modules\Returns\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface $repo */
        $repo = app(\Modules\Returns\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface::class);
        $repo->save($updated);

        return response()->json(new SalesReturnResource($updated));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        /** @var \Modules\Returns\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface $repo */
        $repo = app(\Modules\Returns\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface::class);
        $repo->delete($tenantId, $id);

        return response()->json(null, 204);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->approveSalesReturn($tenantId, $id);

        return response()->json(new SalesReturnResource($entity));
    }

    public function complete(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->completeSalesReturn($tenantId, $id);

        return response()->json(new SalesReturnResource($entity));
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $entity = $this->service->cancelSalesReturn($tenantId, $id);

        return response()->json(new SalesReturnResource($entity));
    }
}
