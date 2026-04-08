<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\InventoryServiceClient;
use App\Exceptions\DistributedTransactionException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OrderController — handles CRUD for Orders while coordinating
 * distributed transactions with the Inventory Service.
 *
 * Pattern: Saga (choreography-based) with compensating transactions.
 * Each mutating operation:
 *   1. Begins a local DB transaction.
 *   2. Calls the remote Inventory Service.
 *   3. Commits locally only if the remote call succeeds.
 *   4. On any failure, rolls back locally AND sends a compensating
 *      request to the Inventory Service to undo its side-effects.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService           $orderService,
        private readonly InventoryServiceClient $inventoryClient,
    ) {}

    // ------------------------------------------------------------------ //
    //  READ
    // ------------------------------------------------------------------ //

    /**
     * GET /orders
     * List all orders with their reserved inventory snapshot.
     */
    public function index(): JsonResponse
    {
        $orders = Order::with('items')->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    /**
     * GET /orders/{id}
     * Show a single order.
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::with('items')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    // ------------------------------------------------------------------ //
    //  CREATE
    // ------------------------------------------------------------------ //

    /**
     * POST /orders
     *
     * Distributed flow:
     *   [Order DB txn begins]
     *     → Create order record (status = pending)
     *     → POST /inventory/reserve  (Inventory Service reserves stock)
     *     → Update order status = confirmed
     *   [Order DB txn commits]
     *
     * On failure at any step:
     *   → Order DB txn is rolled back (order never persisted)
     *   → DELETE /inventory/reserve/{reservationId}  (compensating call)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id'        => 'required|integer',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        $reservationId = null;

        DB::beginTransaction();

        try {
            // Step 1 — persist the order locally (status: pending)
            $order = $this->orderService->createPending($validated);

            Log::info("[OrderService] Order #{$order->id} created (pending).");

            // Step 2 — reserve inventory in the remote service
            $reservation = $this->inventoryClient->reserve(
                orderId : $order->id,
                items   : $validated['items'],
            );

            $reservationId = $reservation['reservation_id'];

            Log::info("[InventoryService] Reservation {$reservationId} created for order #{$order->id}.");

            // Step 3 — confirm the order now that inventory is secured
            $order->confirm($reservationId);

            DB::commit();

            Log::info("[OrderService] Order #{$order->id} confirmed. Txn committed.");

            return response()->json([
                'success'        => true,
                'message'        => 'Order created and inventory reserved.',
                'order_id'       => $order->id,
                'reservation_id' => $reservationId,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error("[OrderService] Create failed — rolling back. Error: {$e->getMessage()}");

            // Compensating transaction: release the reservation if it was created
            if ($reservationId !== null) {
                $this->safeCompensate(fn () =>
                    $this->inventoryClient->cancelReservation($reservationId)
                , "cancel reservation {$reservationId}");
            }

            throw new DistributedTransactionException(
                "Order creation failed: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    // ------------------------------------------------------------------ //
    //  UPDATE
    // ------------------------------------------------------------------ //

    /**
     * PUT /orders/{id}
     *
     * Distributed flow:
     *   [Order DB txn begins]
     *     → Lock order row (FOR UPDATE)
     *     → PATCH /inventory/reserve/{reservationId}  (adjust quantities)
     *     → Update order items locally
     *   [Order DB txn commits]
     *
     * On failure:
     *   → Order DB txn rolled back (original data intact)
     *   → PATCH /inventory/reserve/{reservationId} with original items (compensate)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::with('items')->lockForUpdate()->findOrFail($id);

            $originalItems     = $order->items->toArray();
            $originalReservId  = $order->reservation_id;

            // Step 1 — adjust inventory reservation remotely
            $this->inventoryClient->adjustReservation(
                reservationId : $originalReservId,
                newItems      : $validated['items'],
            );

            Log::info("[InventoryService] Reservation {$originalReservId} adjusted.");

            // Step 2 — update order items locally
            $this->orderService->updateItems($order, $validated['items']);

            DB::commit();

            Log::info("[OrderService] Order #{$id} updated. Txn committed.");

            return response()->json([
                'success'  => true,
                'message'  => 'Order updated.',
                'order_id' => $id,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error("[OrderService] Update #{$id} failed — rolling back. Error: {$e->getMessage()}");

            // Compensating transaction: restore original quantities in inventory
            if (isset($originalReservId, $originalItems)) {
                $this->safeCompensate(fn () =>
                    $this->inventoryClient->adjustReservation($originalReservId, $originalItems)
                , "restore reservation {$originalReservId}");
            }

            throw new DistributedTransactionException(
                "Order update failed: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    // ------------------------------------------------------------------ //
    //  DELETE
    // ------------------------------------------------------------------ //

    /**
     * DELETE /orders/{id}
     *
     * Distributed flow:
     *   [Order DB txn begins]
     *     → Soft-delete order (status = cancelled)
     *     → DELETE /inventory/reserve/{reservationId}  (release stock)
     *   [Order DB txn commits]
     *
     * On failure:
     *   → Order DB txn rolled back (order remains active)
     *   → POST /inventory/reserve  to re-create the reservation (compensate)
     */
    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $order = Order::with('items')->lockForUpdate()->findOrFail($id);

            if ($order->isCancelled()) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Order already cancelled.'], 409);
            }

            $reservationId  = $order->reservation_id;
            $snapshotItems  = $order->items->toArray();

            // Step 1 — cancel the order locally
            $order->cancel();

            // Step 2 — release inventory in the remote service
            $this->inventoryClient->cancelReservation($reservationId);

            Log::info("[InventoryService] Reservation {$reservationId} released.");

            DB::commit();

            Log::info("[OrderService] Order #{$id} deleted. Txn committed.");

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled and inventory released.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error("[OrderService] Delete #{$id} failed — rolling back. Error: {$e->getMessage()}");

            // Compensating transaction: re-create the reservation if it was released
            if (isset($reservationId, $snapshotItems)) {
                $this->safeCompensate(fn () =>
                    $this->inventoryClient->reserve($id, $snapshotItems)
                , "re-create reservation for order #{$id}");
            }

            throw new DistributedTransactionException(
                "Order deletion failed: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    // ------------------------------------------------------------------ //
    //  Helpers
    // ------------------------------------------------------------------ //

    /**
     * Execute a compensating transaction, swallowing any secondary failure
     * so we don't mask the original exception. In production you would push
     * failed compensations to a dead-letter queue / outbox table.
     */
    private function safeCompensate(callable $fn, string $description): void
    {
        try {
            $fn();
            Log::info("[Compensation] Succeeded: {$description}");
        } catch (\Throwable $ce) {
            // Log the compensation failure for manual remediation / alerting.
            Log::critical(
                "[Compensation] FAILED: {$description}. Manual remediation required!",
                ['error' => $ce->getMessage()]
            );
        }
    }
}
