<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ReservationController — manages stock reservations on behalf of the Order Service.
 *
 * Each endpoint is IDEMPOTENT: the Idempotency-Key header (sent by
 * InventoryServiceClient) prevents duplicate side-effects when a request is
 * retried after a network failure.
 *
 * Idempotency is implemented via a simple `idempotency_keys` table that
 * stores the response for each key. On a duplicate request the stored
 * response is returned immediately without re-executing the logic.
 */
class ReservationController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    // ------------------------------------------------------------------ //
    //  CREATE reservation
    //  POST /api/inventory/reservations
    // ------------------------------------------------------------------ //

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'           => 'required|integer',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');

        // Return cached response for duplicate requests
        if ($idempotencyKey && $cached = $this->getCachedResponse($idempotencyKey)) {
            return response()->json($cached, 200);
        }

        DB::beginTransaction();

        try {
            // Check and decrement stock for every item atomically
            $this->inventoryService->checkAndReserveStock($validated['items']);

            $reservation = Reservation::create([
                'id'       => Str::uuid(),
                'order_id' => $validated['order_id'],
                'status'   => 'active',
            ]);

            foreach ($validated['items'] as $item) {
                $reservation->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                ]);
            }

            DB::commit();

            Log::info("[InventoryService] Reservation {$reservation->id} created.");

            $responseBody = [
                'reservation_id' => $reservation->id,
                'order_id'       => $reservation->order_id,
                'status'         => $reservation->status,
                'items'          => $reservation->items,
            ];

            if ($idempotencyKey) {
                $this->cacheResponse($idempotencyKey, $responseBody);
            }

            return response()->json($responseBody, 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("[InventoryService] Reservation creation failed: {$e->getMessage()}");
            throw $e;
        }
    }

    // ------------------------------------------------------------------ //
    //  UPDATE reservation  (adjust quantities on order update)
    //  PATCH /api/inventory/reservations/{reservationId}
    // ------------------------------------------------------------------ //

    public function update(Request $request, string $reservationId): JsonResponse
    {
        $validated = $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey && $cached = $this->getCachedResponse($idempotencyKey)) {
            return response()->json($cached);
        }

        DB::beginTransaction();

        try {
            $reservation = Reservation::with('items')
                ->where('status', 'active')
                ->lockForUpdate()
                ->findOrFail($reservationId);

            // Release old stock, then check-and-reserve new stock
            $this->inventoryService->releaseStock($reservation->items->toArray());
            $this->inventoryService->checkAndReserveStock($validated['items']);

            // Replace items
            $reservation->items()->delete();
            foreach ($validated['items'] as $item) {
                $reservation->items()->create($item);
            }

            DB::commit();

            Log::info("[InventoryService] Reservation {$reservationId} adjusted.");

            $responseBody = [
                'reservation_id' => $reservation->id,
                'status'         => $reservation->status,
                'items'          => $reservation->fresh('items')->items,
            ];

            if ($idempotencyKey) {
                $this->cacheResponse($idempotencyKey, $responseBody);
            }

            return response()->json($responseBody);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("[InventoryService] Reservation adjustment failed: {$e->getMessage()}");
            throw $e;
        }
    }

    // ------------------------------------------------------------------ //
    //  DELETE reservation  (order cancelled or rollback compensation)
    //  DELETE /api/inventory/reservations/{reservationId}
    // ------------------------------------------------------------------ //

    public function destroy(Request $request, string $reservationId): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey && $cached = $this->getCachedResponse($idempotencyKey)) {
            return response()->json($cached);
        }

        DB::beginTransaction();

        try {
            $reservation = Reservation::with('items')->find($reservationId);

            if (!$reservation || $reservation->status === 'cancelled') {
                DB::rollBack();
                // Idempotent: already gone is fine
                return response()->json(['message' => 'Reservation already cancelled or not found.'], 404);
            }

            // Return stock to available pool
            $this->inventoryService->releaseStock($reservation->items->toArray());

            $reservation->update(['status' => 'cancelled']);
            $reservation->items()->delete();

            DB::commit();

            Log::info("[InventoryService] Reservation {$reservationId} cancelled / stock released.");

            $responseBody = ['message' => 'Reservation cancelled and stock released.'];

            if ($idempotencyKey) {
                $this->cacheResponse($idempotencyKey, $responseBody);
            }

            return response()->json($responseBody);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("[InventoryService] Reservation cancellation failed: {$e->getMessage()}");
            throw $e;
        }
    }

    // ------------------------------------------------------------------ //
    //  READ  (useful for debugging / health checks)
    // ------------------------------------------------------------------ //

    public function show(string $reservationId): JsonResponse
    {
        $reservation = Reservation::with('items')->findOrFail($reservationId);

        return response()->json($reservation);
    }

    // ------------------------------------------------------------------ //
    //  Idempotency helpers  (in production: use Redis with TTL)
    // ------------------------------------------------------------------ //

    private function getCachedResponse(string $key): ?array
    {
        $record = DB::table('idempotency_keys')
            ->where('key', $key)
            ->where('created_at', '>=', now()->subHours(24))
            ->first();

        return $record ? json_decode($record->response, true) : null;
    }

    private function cacheResponse(string $key, array $response): void
    {
        DB::table('idempotency_keys')->upsert(
            [['key' => $key, 'response' => json_encode($response), 'created_at' => now()]],
            ['key'],
            ['response', 'created_at'],
        );
    }
}
