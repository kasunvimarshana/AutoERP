<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Order\Application\Contracts\PurchaseOrderServiceInterface;
use Modules\Order\Infrastructure\Http\Resources\PurchaseOrderResource;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderServiceInterface $purchaseOrderService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $orders = $this->purchaseOrderService->getAllPurchaseOrders($tenantId);
        return response()->json(PurchaseOrderResource::collection($orders));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $order = $this->purchaseOrderService->createPurchaseOrder($tenantId, $request->all());
        return response()->json(new PurchaseOrderResource($order), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $order = $this->purchaseOrderService->getPurchaseOrder($tenantId, $id);
        return response()->json(new PurchaseOrderResource($order));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $order = $this->purchaseOrderService->updatePurchaseOrder($tenantId, $id, $request->all());
        return response()->json(new PurchaseOrderResource($order));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->purchaseOrderService->deletePurchaseOrder($tenantId, $id);
        return response()->json(null, 204);
    }

    public function confirm(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $order = $this->purchaseOrderService->confirmPurchaseOrder($tenantId, $id);
        return response()->json(new PurchaseOrderResource($order));
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $order = $this->purchaseOrderService->cancelPurchaseOrder($tenantId, $id);
        return response()->json(new PurchaseOrderResource($order));
    }
}
