<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Order\DTOs\CreateOrderDTO;
use App\Application\Order\Services\OrderService;
use App\Domain\Order\Exceptions\OrderNotFoundException;
use App\Domain\Order\Saga\SagaFailedException;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Resources\Order\OrderCollection;
use App\Http\Resources\Order\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * OrderController
 *
 * Thin controller — all logic in OrderService.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    // GET /api/orders
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $filters  = $request->only(['status', 'user_id', 'payment_status']);
        $perPage  = (int) $request->get('per_page', 15);

        $orders = $this->orderService->list($tenantId, $filters, $perPage);

        return (new OrderCollection($orders))->response();
    }

    // GET /api/orders/{id}
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $tenantId = $request->attributes->get('tenant_id');
            $order    = $this->orderService->findById($id, $tenantId);

            return (new OrderResource($order))->response();

        } catch (OrderNotFoundException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => true], 404);
        }
    }

    // POST /api/orders
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), [
                'tenant_id'    => $request->attributes->get('tenant_id'),
                'user_id'      => $request->user()->id,
                'service_token'=> $request->bearerToken(),
            ]);

            $order = $this->orderService->createViaOrchestrator(
                CreateOrderDTO::fromArray($data)
            );

            return (new OrderResource($order))->response()->setStatusCode(201);

        } catch (SagaFailedException $e) {
            return response()->json([
                'message'    => 'Order could not be completed. All changes have been rolled back.',
                'saga_id'    => $e->sagaId,
                'failed_step'=> $e->failedStep,
                'error'      => true,
            ], 422);
        }
    }

    // GET /api/orders/{id}/saga-status
    public function sagaStatus(string $sagaId): JsonResponse
    {
        // Instantiate a bare orchestrator just for status lookups
        $orchestrator = new \App\Infrastructure\Saga\SagaOrchestrator();

        return response()->json([
            'data' => $orchestrator->getStatus($sagaId),
        ]);
    }
}
