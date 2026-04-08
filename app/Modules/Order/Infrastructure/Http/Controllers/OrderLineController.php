<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Order\Application\Contracts\OrderLineServiceInterface;
use Modules\Order\Infrastructure\Http\Resources\OrderLineResource;

class OrderLineController extends Controller
{
    public function __construct(
        private readonly OrderLineServiceInterface $orderLineService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $lines = $this->orderLineService->getLinesForOrder(
            $tenantId,
            (string) $request->query('order_type', ''),
            (string) $request->query('order_id', ''),
        );
        return response()->json(OrderLineResource::collection($lines));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $line = $this->orderLineService->addOrderLine($tenantId, $request->all());
        return response()->json(new OrderLineResource($line), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $line = $this->orderLineService->getOrderLine($tenantId, $id);
        return response()->json(new OrderLineResource($line));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $line = $this->orderLineService->updateOrderLine($tenantId, $id, $request->all());
        return response()->json(new OrderLineResource($line));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->orderLineService->deleteOrderLine($tenantId, $id);
        return response()->json(null, 204);
    }
}
