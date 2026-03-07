<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\SagaTransaction;
use App\Saga\OrderSagaOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private readonly OrderSagaOrchestrator $orchestrator) {}

    // -------------------------------------------------------------------------
    // GET /api/v1/orders
    // -------------------------------------------------------------------------

    /**
     * List orders for the authenticated tenant, paginated.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $perPage  = (int) $request->query('per_page', 15);
        $perPage  = min(max($perPage, 1), 100);

        $orders = Order::byTenant($tenantId)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data'  => $orders->map(fn (Order $o) => $o->toApiArray()),
            'meta'  => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/orders
    // -------------------------------------------------------------------------

    /**
     * Create a new order and start the Order Placement Saga.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $orderData = array_merge($request->validated(), [
            'tenant_id' => $tenantId,
        ]);

        try {
            $order = $this->orchestrator->start($orderData);

            return response()->json([
                'message' => 'Order accepted. Saga started.',
                'data'    => $order->toApiArray(),
                'saga_id' => $order->saga_id,
            ], 202);
        } catch (Throwable $e) {
            Log::error('[OrderController] Saga failed to start.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to place order.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal error.',
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/orders/{id}
    // -------------------------------------------------------------------------

    /**
     * Show an order together with its saga transaction log.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $order = Order::byTenant($tenantId)->with('sagaTransactions')->find($id);

        if ($order === null) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json([
            'data' => array_merge($order->toApiArray(), [
                'saga_transactions' => $order->sagaTransactions
                    ->map(fn (SagaTransaction $t) => $t->toApiArray())
                    ->values(),
            ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/orders/{id}
    // -------------------------------------------------------------------------

    /**
     * Cancel a pending order by triggering saga compensation.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $order = Order::byTenant($tenantId)->find($id);

        if ($order === null) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if (! $order->isPending()) {
            return response()->json([
                'message' => "Cannot cancel an order with status '{$order->status}'.",
            ], 422);
        }

        try {
            $this->orchestrator->handleStepFailure(
                $order->saga_id,
                'CREATE_ORDER',
                'Cancelled by customer',
            );

            return response()->json([
                'message' => 'Cancellation initiated.',
                'data'    => $order->fresh()?->toApiArray(),
            ]);
        } catch (Throwable $e) {
            Log::error('[OrderController] Cancel failed.', [
                'order_id' => $id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to cancel order.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal error.',
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/orders/{id}/saga-status
    // -------------------------------------------------------------------------

    /**
     * Return real-time saga status from Redis (fast path) with DB fallback.
     */
    public function sagaStatus(Request $request, int $id): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $order = Order::byTenant($tenantId)->find($id);

        if ($order === null) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        // Primary: Redis (fast, real-time).
        $state = $this->orchestrator->getSagaState($order->saga_id ?? '');

        // Fallback: derive from DB transactions.
        if (empty($state)) {
            $transactions = SagaTransaction::where('order_id', $id)
                ->orderBy('created_at')
                ->get();

            $state = [
                'saga_id'     => $order->saga_id,
                'order_id'    => $order->id,
                'status'      => $order->status,
                'source'      => 'database',
                'steps'       => $transactions->map(fn (SagaTransaction $t) => $t->toApiArray())->values(),
            ];
        } else {
            $state['source'] = 'redis';
        }

        return response()->json(['data' => $state]);
    }

    // -------------------------------------------------------------------------
    // GET /api/health
    // -------------------------------------------------------------------------

    public function health(): JsonResponse
    {
        return response()->json(['status' => 'ok', 'service' => 'order-service']);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the tenant ID from the request headers (set by API Gateway) or
     * fall back to 1 during local development.
     */
    private function resolveTenantId(Request $request): int
    {
        return (int) ($request->header('X-Tenant-ID') ?? $request->query('tenant_id', 1));
    }
}
