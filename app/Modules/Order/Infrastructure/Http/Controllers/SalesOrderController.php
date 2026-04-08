<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Order\Application\Contracts\SalesOrderServiceInterface;
use Modules\Order\Infrastructure\Http\Resources\SalesOrderResource;

class SalesOrderController extends Controller
{
    public function __construct(
        private readonly SalesOrderServiceInterface $salesOrderService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $orders = $this->salesOrderService->getAllSalesOrders($tenantId);
        return response()->json(SalesOrderResource::collection($orders));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $order = $this->salesOrderService->createSalesOrder($tenantId, $request->all());
        return response()->json(new SalesOrderResource($order), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $order = $this->salesOrderService->getSalesOrder($tenantId, $id);
        return response()->json(new SalesOrderResource($order));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $order = $this->salesOrderService->updateSalesOrder($tenantId, $id, $request->all());
        return response()->json(new SalesOrderResource($order));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->salesOrderService->deleteSalesOrder($tenantId, $id);
        return response()->json(null, 204);
    }

    public function confirm(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $order = $this->salesOrderService->confirmSalesOrder($tenantId, $id);
        return response()->json(new SalesOrderResource($order));
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $order = $this->salesOrderService->cancelSalesOrder($tenantId, $id);
        return response()->json(new SalesOrderResource($order));
    }
}
