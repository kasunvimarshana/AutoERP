<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    /**
     * Display a listing of all inventory items.
     */
    public function index(): JsonResponse
    {
        $inventories = Inventory::all();

        return response()->json([
            'success' => true,
            'data' => $inventories,
        ]);
    }

    /**
     * Store a newly created inventory item.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'sku' => 'required|string|max:100|unique:inventories,sku',
                'quantity' => 'required|integer|min:0',
                'unit_price' => 'required|numeric|min:0',
                'status' => 'sometimes|string|in:active,inactive',
            ]);

            DB::beginTransaction();

            $inventory = Inventory::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory item created successfully.',
                'data' => $inventory,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create inventory item.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified inventory item.
     */
    public function show(string $id): JsonResponse
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    /**
     * Update the specified inventory item.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found.',
            ], 404);
        }

        try {
            $validated = $request->validate([
                'product_name' => 'sometimes|string|max:255',
                'sku' => 'sometimes|string|max:100|unique:inventories,sku,' . $id,
                'quantity' => 'sometimes|integer|min:0',
                'unit_price' => 'sometimes|numeric|min:0',
                'status' => 'sometimes|string|in:active,inactive',
            ]);

            DB::beginTransaction();

            $inventory->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory item updated successfully.',
                'data' => $inventory->fresh(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update inventory item.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified inventory item.
     */
    public function destroy(string $id): JsonResponse
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found.',
            ], 404);
        }

        try {
            DB::beginTransaction();

            $inventory->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory item deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inventory item.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reserve inventory quantity for an order (called by Order Service).
     * This is part of the distributed transaction saga.
     */
    public function reserve(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sku' => 'required|string',
                'quantity' => 'required|integer|min:1',
                'order_id' => 'required|integer',
            ]);

            DB::beginTransaction();

            $inventory = Inventory::where('sku', $validated['sku'])
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Inventory item not found or inactive.',
                ], 404);
            }

            $available = $inventory->quantity - $inventory->reserved_quantity;

            if ($available < $validated['quantity']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient inventory. Available: {$available}, Requested: {$validated['quantity']}.",
                ], 409);
            }

            $inventory->increment('reserved_quantity', $validated['quantity']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory reserved successfully.',
                'data' => [
                    'sku' => $inventory->sku,
                    'reserved_quantity' => $validated['quantity'],
                    'order_id' => $validated['order_id'],
                    'available_after_reservation' => $available - $validated['quantity'],
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reserve inventory.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Release (rollback) a previously reserved inventory quantity.
     * This is the compensating transaction used during saga rollback.
     */
    public function release(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sku' => 'required|string',
                'quantity' => 'required|integer|min:1',
                'order_id' => 'required|integer',
            ]);

            DB::beginTransaction();

            $inventory = Inventory::where('sku', $validated['sku'])
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Inventory item not found.',
                ], 404);
            }

            $releaseAmount = min($validated['quantity'], $inventory->reserved_quantity);
            $inventory->decrement('reserved_quantity', $releaseAmount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory reservation released successfully (compensating transaction).',
                'data' => [
                    'sku' => $inventory->sku,
                    'released_quantity' => $releaseAmount,
                    'order_id' => $validated['order_id'],
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to release inventory reservation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fulfill inventory for a completed order (reduce actual quantity).
     * Called when an order is confirmed/completed.
     */
    public function fulfill(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sku' => 'required|string',
                'quantity' => 'required|integer|min:1',
                'order_id' => 'required|integer',
            ]);

            DB::beginTransaction();

            $inventory = Inventory::where('sku', $validated['sku'])
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Inventory item not found.',
                ], 404);
            }

            $deductAmount = min($validated['quantity'], $inventory->reserved_quantity);
            $inventory->decrement('reserved_quantity', $deductAmount);
            $inventory->decrement('quantity', $deductAmount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory fulfilled successfully.',
                'data' => [
                    'sku' => $inventory->sku,
                    'fulfilled_quantity' => $deductAmount,
                    'order_id' => $validated['order_id'],
                    'remaining_quantity' => $inventory->fresh()->quantity,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to fulfill inventory.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

