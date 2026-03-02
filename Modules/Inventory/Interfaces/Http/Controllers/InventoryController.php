<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Inventory\Application\DTOs\StockBatchDTO;
use Modules\Inventory\Application\DTOs\StockTransactionDTO;
use Modules\Inventory\Application\Services\InventoryService;

/**
 * Inventory controller.
 *
 * Input validation, authorization, and response formatting only.
 * All business logic is delegated to InventoryService.
 *
 * @OA\Tag(name="Inventory", description="Inventory management endpoints")
 */
class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/inventory/stock",
     *     tags={"Inventory"},
     *     summary="List all stock items (paginated, tenant-scoped)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated list of stock items"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listStockItems(Request $request): JsonResponse
    {
        $perPage    = (int) $request->query('per_page', 15);
        $stockItems = $this->service->listStockItems($perPage);

        return ApiResponse::success($stockItems, 'Stock items retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/inventory/transactions",
     *     tags={"Inventory"},
     *     summary="Record a stock transaction",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transaction_type","warehouse_id","product_id","uom_id","quantity","unit_cost"},
     *             @OA\Property(property="transaction_type", type="string", enum={"purchase_receipt","sales_shipment","internal_transfer","adjustment","return"}),
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(property="product_id", type="integer"),
     *             @OA\Property(property="uom_id", type="integer"),
     *             @OA\Property(property="quantity", type="string", example="10.0000"),
     *             @OA\Property(property="unit_cost", type="string", example="5.5000"),
     *             @OA\Property(property="batch_number", type="string", nullable=true),
     *             @OA\Property(property="lot_number", type="string", nullable=true),
     *             @OA\Property(property="serial_number", type="string", nullable=true),
     *             @OA\Property(property="expiry_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="is_pharmaceutical_compliant", type="boolean", default=false)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Transaction recorded"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function recordTransaction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_type'            => ['required', 'string', 'in:purchase_receipt,sales_shipment,internal_transfer,adjustment,return'],
            'warehouse_id'                => ['required', 'integer'],
            'product_id'                  => ['required', 'integer'],
            'uom_id'                      => ['required', 'integer'],
            'quantity'                    => ['required', 'numeric', 'gt:0'],
            'unit_cost'                   => ['required', 'numeric', 'min:0'],
            'batch_number'                => ['nullable', 'string', 'max:255'],
            'lot_number'                  => ['nullable', 'string', 'max:255'],
            'serial_number'               => ['nullable', 'string', 'max:255'],
            'expiry_date'                 => ['nullable', 'date'],
            'notes'                       => ['nullable', 'string'],
            'is_pharmaceutical_compliant' => ['nullable', 'boolean'],
        ]);

        $dto         = StockTransactionDTO::fromArray($validated);
        $transaction = $this->service->recordTransaction($dto);

        return ApiResponse::created($transaction, 'Stock transaction recorded.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/inventory/stock/{productId}/{warehouseId}",
     *     tags={"Inventory"},
     *     summary="Get aggregated stock level for a product in a warehouse",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="warehouseId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Stock level data"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getStockLevel(int $productId, int $warehouseId): JsonResponse
    {
        $level = $this->service->getStockLevel($productId, $warehouseId);

        return ApiResponse::success($level, 'Stock level retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/inventory/reservations",
     *     tags={"Inventory"},
     *     summary="Reserve stock for a reference",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","warehouse_id","quantity_reserved","reference_type","reference_id"},
     *             @OA\Property(property="product_id", type="integer"),
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(property="quantity_reserved", type="string", example="5.0000"),
     *             @OA\Property(property="reference_type", type="string"),
     *             @OA\Property(property="reference_id", type="integer"),
     *             @OA\Property(property="expires_at", type="string", format="date-time", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Stock reserved"),
     *     @OA\Response(response=422, description="Validation error or insufficient stock"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function reserve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'        => ['required', 'integer'],
            'warehouse_id'      => ['required', 'integer'],
            'quantity_reserved' => ['required', 'numeric', 'gt:0'],
            'reference_type'    => ['required', 'string', 'max:255'],
            'reference_id'      => ['required', 'integer'],
            'expires_at'        => ['nullable', 'date'],
        ]);

        $reservation = $this->service->reserve($validated);

        return ApiResponse::created($reservation, 'Stock reserved.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/inventory/reservations/{id}",
     *     tags={"Inventory"},
     *     summary="Release a stock reservation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Reservation released"),
     *     @OA\Response(response=404, description="Reservation not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function releaseReservation(int $id): JsonResponse
    {
        $this->service->releaseReservation($id);

        return ApiResponse::success(message: 'Stock reservation released.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/inventory/products/{productId}/transactions",
     *     tags={"Inventory"},
     *     summary="List paginated stock transactions for a product",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated list of stock transactions"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listTransactions(Request $request, int $productId): JsonResponse
    {
        $perPage      = (int) $request->query('per_page', 15);
        $transactions = $this->service->listTransactions($productId, $perPage);

        return ApiResponse::success($transactions, 'Stock transactions retrieved.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/inventory/fefo/{productId}/{warehouseId}",
     *     tags={"Inventory"},
     *     summary="List stock items ordered by FEFO (First-Expired, First-Out) for pharmaceutical compliance",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="warehouseId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Stock items ordered by expiry date ascending"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getStockByFEFO(int $productId, int $warehouseId): JsonResponse
    {
        $stockItems = $this->service->getStockByFEFO($productId, $warehouseId);

        return ApiResponse::success($stockItems, 'FEFO stock items retrieved.');
    }

    // =========================================================================
    // Batch / Lot Management — Full CRUD
    // =========================================================================

    /**
     * @OA\Post(
     *     path="/api/v1/inventory/batches",
     *     tags={"Inventory"},
     *     summary="Create a new batch (stock item) record",
     *     description="Creates a new batch with known quantity, cost price, lot number, and expiry date. Automatically records a purchase_receipt ledger entry.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"warehouse_id","product_id","uom_id","quantity","cost_price"},
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(property="product_id", type="integer"),
     *             @OA\Property(property="uom_id", type="integer"),
     *             @OA\Property(property="quantity", type="string", example="100.0000"),
     *             @OA\Property(property="cost_price", type="string", example="5.5000"),
     *             @OA\Property(property="batch_number", type="string", nullable=true),
     *             @OA\Property(property="lot_number", type="string", nullable=true),
     *             @OA\Property(property="serial_number", type="string", nullable=true),
     *             @OA\Property(property="expiry_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="costing_method", type="string", enum={"fifo","lifo","weighted_average"}, default="fifo"),
     *             @OA\Property(property="stock_location_id", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Batch created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function createBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id'      => ['required', 'integer'],
            'product_id'        => ['required', 'integer'],
            'uom_id'            => ['required', 'integer'],
            'quantity'          => ['required', 'numeric', 'gt:0'],
            'cost_price'        => ['required', 'numeric', 'min:0'],
            'batch_number'      => ['nullable', 'string', 'max:255'],
            'lot_number'        => ['nullable', 'string', 'max:255'],
            'serial_number'     => ['nullable', 'string', 'max:255'],
            'expiry_date'       => ['nullable', 'date'],
            'costing_method'    => ['nullable', 'string', 'in:fifo,lifo,weighted_average'],
            'stock_location_id' => ['nullable', 'integer'],
        ]);

        $dto   = StockBatchDTO::fromArray($validated);
        $batch = $this->service->createBatch($dto);

        return ApiResponse::created($batch, 'Batch created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/inventory/batches/{id}",
     *     tags={"Inventory"},
     *     summary="Show a batch (stock item) by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Batch record"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function showBatch(int $id): JsonResponse
    {
        $batch = $this->service->showBatch($id);

        return ApiResponse::success($batch, 'Batch retrieved.');
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/inventory/batches/{id}",
     *     tags={"Inventory"},
     *     summary="Update mutable fields of a batch record",
     *     description="Only cost_price, expiry_date, lot_number, batch_number, and costing_method may be updated.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="cost_price", type="string", nullable=true),
     *             @OA\Property(property="expiry_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="lot_number", type="string", nullable=true),
     *             @OA\Property(property="batch_number", type="string", nullable=true),
     *             @OA\Property(property="costing_method", type="string", enum={"fifo","lifo","weighted_average"}, nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Batch updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function updateBatch(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'cost_price'     => ['nullable', 'numeric', 'min:0'],
            'expiry_date'    => ['nullable', 'date'],
            'lot_number'     => ['nullable', 'string', 'max:255'],
            'batch_number'   => ['nullable', 'string', 'max:255'],
            'costing_method' => ['nullable', 'string', 'in:fifo,lifo,weighted_average'],
        ]);

        $batch = $this->service->updateBatch($id, $validated);

        return ApiResponse::success($batch, 'Batch updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/inventory/batches/{id}",
     *     tags={"Inventory"},
     *     summary="Delete a batch (stock item) record",
     *     description="Only batches with zero on-hand and reserved quantities can be deleted.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Batch deleted"),
     *     @OA\Response(response=422, description="Cannot delete batch with remaining stock"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function deleteBatch(int $id): JsonResponse
    {
        $this->service->deleteBatch($id);

        return ApiResponse::success(message: 'Batch deleted.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/inventory/batches/deduct",
     *     tags={"Inventory"},
     *     summary="Deduct stock using a costing strategy (FIFO, LIFO, FEFO, or manual batch selection)",
     *     description="Deducts the requested quantity from the correct batches in strategy order. Each batch touched produces an individual ledger transaction.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","warehouse_id","uom_id","quantity","unit_cost"},
     *             @OA\Property(property="product_id", type="integer"),
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(property="uom_id", type="integer"),
     *             @OA\Property(property="quantity", type="string", example="5.0000"),
     *             @OA\Property(property="unit_cost", type="string", example="5.5000"),
     *             @OA\Property(property="strategy", type="string", enum={"fifo","lifo","fefo","manual"}, default="fifo"),
     *             @OA\Property(property="batch_number", type="string", nullable=true, description="Required when strategy=manual"),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="is_pharmaceutical_compliant", type="boolean", default=false)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Deduction results per batch"),
     *     @OA\Response(response=422, description="Insufficient stock or validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function deductByStrategy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'                  => ['required', 'integer'],
            'warehouse_id'                => ['required', 'integer'],
            'uom_id'                      => ['required', 'integer'],
            'quantity'                    => ['required', 'numeric', 'gt:0'],
            'unit_cost'                   => ['required', 'numeric', 'min:0'],
            'strategy'                    => ['nullable', 'string', 'in:fifo,lifo,fefo,manual'],
            'batch_number'                => ['nullable', 'string', 'max:255'],
            'notes'                       => ['nullable', 'string'],
            'is_pharmaceutical_compliant' => ['nullable', 'boolean'],
        ]);

        $deductions = $this->service->deductByStrategy(
            productId:               (int) $validated['product_id'],
            warehouseId:             (int) $validated['warehouse_id'],
            uomId:                   (int) $validated['uom_id'],
            quantity:                (string) $validated['quantity'],
            unitCost:                (string) $validated['unit_cost'],
            strategy:                $validated['strategy'] ?? 'fifo',
            batchNumber:             $validated['batch_number'] ?? null,
            notes:                   $validated['notes'] ?? null,
            isPharmaceuticalCompliant: (bool) ($validated['is_pharmaceutical_compliant'] ?? false),
        );

        return ApiResponse::success($deductions, 'Stock deducted by strategy.');
    }
}
