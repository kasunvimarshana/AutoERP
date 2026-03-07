<?php

namespace App\Http\Controllers;

use App\Exceptions\ServiceException;
use App\Models\Order;
use App\Services\InventoryServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(private readonly InventoryServiceClient $inventoryClient)
    {
    }

    /**
     * Display a listing of all orders.
     */
    public function index(): JsonResponse
    {
        $orders = Order::all();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Create a new order with distributed transaction handling.
     *
     * This method implements the Saga pattern:
     * 1. Start a local DB transaction
     * 2. Reserve inventory in the Inventory Service (remote call)
     * 3. Create the order record locally
     * 4. If local creation fails, release the inventory reservation (compensating transaction)
     * 5. If everything succeeds, commit the local transaction
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'product_sku' => 'required|string|max:100',
                'product_name' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1',
                'unit_price' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $validated['total_price'] = $validated['quantity'] * $validated['unit_price'];

        // We use a placeholder ID for the saga steps before the order is persisted.
        // The actual order ID is assigned after creation.
        $tempOrderRef = uniqid('order_', true);
        $inventoryReserved = false;

        DB::beginTransaction();

        try {
            // Step 1: Reserve inventory in Inventory Service (remote saga step)
            $this->inventoryClient->reserveInventory(
                $validated['product_sku'],
                $validated['quantity'],
                0 // Placeholder; replaced after order creation
            );
            $inventoryReserved = true;

            // Step 2: Create the order record in local DB
            $order = Order::create($validated);

            // Step 3: Commit local transaction
            DB::commit();

            Log::info("Order #{$order->id} created successfully with inventory reserved.", [
                'sku' => $order->product_sku,
                'quantity' => $order->quantity,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully. Inventory has been reserved.',
                'data' => $order,
            ], 201);
        } catch (ServiceException $e) {
            // Inventory Service call failed — roll back local DB transaction
            DB::rollBack();

            Log::error("Order creation failed due to Inventory Service error. Local transaction rolled back.", [
                'error' => $e->getMessage(),
                'product_sku' => $validated['product_sku'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Order could not be created: ' . $e->getMessage(),
                'transaction_status' => 'rolled_back',
            ], $e->getServiceStatusCode() >= 400 ? $e->getServiceStatusCode() : 503);
        } catch (\Exception $e) {
            // Local DB operation failed — roll back local transaction AND
            // release the inventory reservation (compensating transaction)
            DB::rollBack();

            if ($inventoryReserved) {
                Log::warning("Local order creation failed after inventory was reserved. Executing compensating transaction (release).", [
                    'error' => $e->getMessage(),
                    'product_sku' => $validated['product_sku'],
                    'quantity' => $validated['quantity'],
                    'temp_ref' => $tempOrderRef,
                ]);

                try {
                    $this->inventoryClient->releaseInventory(
                        $validated['product_sku'],
                        $validated['quantity'],
                        0 // Temp ref since order was never saved
                    );
                    Log::info("Compensating transaction succeeded: inventory reservation released.", [
                        'product_sku' => $validated['product_sku'],
                    ]);
                } catch (ServiceException $releaseException) {
                    // Compensating transaction also failed — requires manual intervention
                    Log::critical("SAGA FAILURE: Compensating transaction (release) failed. Manual intervention required.", [
                        'original_error' => $e->getMessage(),
                        'release_error' => $releaseException->getMessage(),
                        'product_sku' => $validated['product_sku'],
                        'quantity' => $validated['quantity'],
                        'temp_ref' => $tempOrderRef,
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Order creation failed. All changes have been rolled back.',
                'transaction_status' => 'rolled_back',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Update an existing order.
     *
     * If the quantity changes, the inventory reservation is adjusted:
     * - Increased quantity: reserve the additional amount
     * - Decreased quantity: release the excess reservation
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if (!in_array($order->status, ['pending'])) {
            return response()->json([
                'success' => false,
                'message' => "Order cannot be updated in '{$order->status}' status. Only 'pending' orders can be updated.",
            ], 409);
        }

        try {
            $validated = $request->validate([
                'customer_name' => 'sometimes|string|max:255',
                'customer_email' => 'sometimes|email|max:255',
                'quantity' => 'sometimes|integer|min:1',
                'unit_price' => 'sometimes|numeric|min:0',
                'notes' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $originalQuantity = $order->quantity;
        $newQuantity = $validated['quantity'] ?? $originalQuantity;
        $quantityDiff = $newQuantity - $originalQuantity;

        if (isset($validated['quantity']) || isset($validated['unit_price'])) {
            $validated['total_price'] = $newQuantity * ($validated['unit_price'] ?? $order->unit_price);
        }

        DB::beginTransaction();

        $inventoryAdjusted = false;
        $adjustmentType = null;

        try {
            // Adjust inventory reservation if quantity changed
            if ($quantityDiff > 0) {
                // Reserve additional inventory
                $this->inventoryClient->reserveInventory(
                    $order->product_sku,
                    $quantityDiff,
                    $order->id
                );
                $inventoryAdjusted = true;
                $adjustmentType = 'reserved_additional';
            } elseif ($quantityDiff < 0) {
                // Release excess inventory reservation
                $this->inventoryClient->releaseInventory(
                    $order->product_sku,
                    abs($quantityDiff),
                    $order->id
                );
                $inventoryAdjusted = true;
                $adjustmentType = 'released_excess';
            }

            $order->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully.',
                'data' => $order->fresh(),
                'inventory_adjustment' => $adjustmentType,
            ]);
        } catch (ServiceException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Order update failed due to Inventory Service error: ' . $e->getMessage(),
                'transaction_status' => 'rolled_back',
            ], $e->getServiceStatusCode() >= 400 ? $e->getServiceStatusCode() : 503);
        } catch (\Exception $e) {
            DB::rollBack();

            // Compensate if inventory was already adjusted
            if ($inventoryAdjusted) {
                try {
                    if ($adjustmentType === 'reserved_additional') {
                        $this->inventoryClient->releaseInventory(
                            $order->product_sku,
                            $quantityDiff,
                            $order->id
                        );
                    } elseif ($adjustmentType === 'released_excess') {
                        $this->inventoryClient->reserveInventory(
                            $order->product_sku,
                            abs($quantityDiff),
                            $order->id
                        );
                    }
                } catch (ServiceException $compensateException) {
                    Log::critical("SAGA FAILURE: Order update compensating transaction failed. Manual intervention required.", [
                        'order_id' => $order->id,
                        'original_error' => $e->getMessage(),
                        'compensate_error' => $compensateException->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Order update failed. All changes have been rolled back.',
                'transaction_status' => 'rolled_back',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel and delete an order.
     *
     * Releases any inventory reservation associated with this order
     * before deleting the record.
     */
    public function destroy(string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if (!$order->isCancellable()) {
            return response()->json([
                'success' => false,
                'message' => "Order cannot be cancelled in '{$order->status}' status.",
            ], 409);
        }

        DB::beginTransaction();

        $inventoryReleased = false;

        try {
            // Release inventory reservation (compensating transaction for the original create/update)
            if (in_array($order->status, ['pending', 'confirmed'])) {
                $this->inventoryClient->releaseInventory(
                    $order->product_sku,
                    $order->quantity,
                    $order->id
                );
                $inventoryReleased = true;
            }

            $order->delete();

            DB::commit();

            Log::info("Order #{$id} deleted and inventory reservation released.", [
                'sku' => $order->product_sku,
                'quantity' => $order->quantity,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order deleted and inventory reservation released.',
                'inventory_released' => $inventoryReleased,
            ]);
        } catch (ServiceException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Order deletion failed due to Inventory Service error: ' . $e->getMessage(),
                'transaction_status' => 'rolled_back',
            ], $e->getServiceStatusCode() >= 400 ? $e->getServiceStatusCode() : 503);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Order deletion failed. All changes have been rolled back.',
                'transaction_status' => 'rolled_back',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm a pending order and fulfill inventory.
     *
     * Changes order status from 'pending' to 'confirmed' and
     * triggers inventory fulfillment (actual stock deduction).
     */
    public function confirm(string $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if (!$order->isConfirmable()) {
            return response()->json([
                'success' => false,
                'message' => "Order cannot be confirmed in '{$order->status}' status. Only 'pending' orders can be confirmed.",
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Fulfill inventory (deduct from stock and reserved quantity)
            $this->inventoryClient->fulfillInventory(
                $order->product_sku,
                $order->quantity,
                $order->id
            );

            $order->update(['status' => 'confirmed']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order confirmed and inventory fulfilled.',
                'data' => $order->fresh(),
            ]);
        } catch (ServiceException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Order confirmation failed: ' . $e->getMessage(),
                'transaction_status' => 'rolled_back',
            ], $e->getServiceStatusCode() >= 400 ? $e->getServiceStatusCode() : 503);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Order confirmation failed. All changes have been rolled back.',
                'transaction_status' => 'rolled_back',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

