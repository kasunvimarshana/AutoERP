<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Inventory\Http\Requests\StockAdjustmentRequest;
use Modules\Inventory\Http\Requests\StockTransactionRequest;
use Modules\Inventory\Services\StockService;

/**
 * Stock Controller
 *
 * Handles HTTP requests for stock management operations.
 */
class StockController extends BaseController
{
    /**
     * Constructor
     */
    public function __construct(
        private StockService $stockService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/inventory/products/{productId}/stock-movements",
     *     summary="Get stock movements for a product",
     *     description="Retrieve paginated list of stock movements (transactions) for a specific product with filtering by warehouse, transaction type, and date range",
     *     operationId="stockMovements",
     *     tags={"Inventory-Stock"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         description="Product ID (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Parameter(
     *         name="warehouse_id",
     *         in="query",
     *         description="Filter by warehouse ID",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005")
     *     ),
     *     @OA\Parameter(
     *         name="transaction_type",
     *         in="query",
     *         description="Filter by transaction type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"receipt", "issue", "adjustment_in", "adjustment_out", "transfer_in", "transfer_out", "return", "reservation", "allocation", "release", "damaged"}, example="receipt")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter transactions from this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter transactions to this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock movements retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/StockTransaction")
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function movements(Request $request, string $productId): JsonResponse
    {
        try {
            $filters = [
                'warehouse_id' => $request->input('warehouse_id'),
                'transaction_type' => $request->input('transaction_type'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'per_page' => $request->integer('per_page', 15),
            ];

            $movements = $this->stockService->getStockMovements($productId, $filters);

            return $this->success($movements);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch stock movements: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/stock/level",
     *     summary="Get current stock level",
     *     description="Retrieve current stock level for a product in a specific warehouse and optional location",
     *     operationId="stockLevel",
     *     tags={"Inventory-Stock"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="product_id",
     *         in="query",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Parameter(
     *         name="warehouse_id",
     *         in="query",
     *         description="Warehouse ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005")
     *     ),
     *     @OA\Parameter(
     *         name="location_id",
     *         in="query",
     *         description="Location ID (optional)",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440007")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock level retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="stock_level", type="number", format="decimal", example=100.00, description="Available stock quantity")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Missing required parameters",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function level(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|uuid',
                'warehouse_id' => 'required|uuid',
                'location_id' => 'nullable|uuid',
            ]);

            $stockLevel = $this->stockService->getStockLevel(
                $request->input('product_id'),
                $request->input('warehouse_id'),
                $request->input('location_id')
            );

            return $this->success(['stock_level' => $stockLevel]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to fetch stock level: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/products/{productId}/total-stock",
     *     summary="Get total stock for a product",
     *     description="Retrieve total stock quantity for a product across all warehouses",
     *     operationId="stockTotalStock",
     *     tags={"Inventory-Stock"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         description="Product ID (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Total stock retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_stock", type="number", format="decimal", example=500.00, description="Total stock across all warehouses")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function totalStock(string $productId): JsonResponse
    {
        try {
            $totalStock = $this->stockService->getTotalStock($productId);

            return $this->success(['total_stock' => $totalStock]);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch total stock: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/inventory/stock/transaction",
     *     summary="Record a stock transaction",
     *     description="Record a new stock transaction (receipt, issue, transfer, etc.)",
     *     operationId="stockTransaction",
     *     tags={"Inventory-Stock"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Stock transaction data",
     *         @OA\JsonContent(ref="#/components/schemas/StockTransactionRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Stock transaction recorded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock transaction recorded successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/StockTransaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Insufficient stock or invalid operation",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function transaction(StockTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = $this->stockService->recordTransaction($request->validated());

            return $this->created($transaction, 'Stock transaction recorded successfully');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to record stock transaction: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/inventory/stock/adjust",
     *     summary="Adjust stock quantity",
     *     description="Adjust stock quantity up or down with a reason (physical count, damaged, etc.)",
     *     operationId="stockAdjust",
     *     tags={"Inventory-Stock"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Stock adjustment data",
     *         @OA\JsonContent(ref="#/components/schemas/StockAdjustmentRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Stock adjusted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock adjusted successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/StockTransaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid adjustment",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function adjust(StockAdjustmentRequest $request): JsonResponse
    {
        try {
            $adjustment = $this->stockService->adjust($request->validated());

            return $this->created($adjustment, 'Stock adjusted successfully');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to adjust stock: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/inventory/stock/reserve",
     *     summary="Reserve stock for an order",
     *     description="Reserve stock quantity for a sales order or other reference document",
     *     operationId="stockReserve",
     *     tags={"Inventory-Stock"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Stock reservation data",
     *         @OA\JsonContent(ref="#/components/schemas/StockReserveRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Stock reserved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock reserved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/StockTransaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Insufficient available stock",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function reserve(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|uuid',
                'warehouse_id' => 'required|uuid',
                'location_id' => 'nullable|uuid',
                'quantity' => 'required|numeric|min:0.01',
                'reference_type' => 'required|string',
                'reference_id' => 'required|uuid',
                'notes' => 'nullable|string',
            ]);

            $reservation = $this->stockService->reserve($request->all());

            return $this->created($reservation, 'Stock reserved successfully');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to reserve stock: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/inventory/stock/allocate",
     *     summary="Allocate reserved stock",
     *     description="Allocate reserved stock for picking/shipment",
     *     operationId="stockAllocate",
     *     tags={"Inventory-Stock"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Stock allocation data",
     *         @OA\JsonContent(ref="#/components/schemas/StockReserveRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Stock allocated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock allocated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/StockTransaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Insufficient available stock",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function allocate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|uuid',
                'warehouse_id' => 'required|uuid',
                'location_id' => 'nullable|uuid',
                'quantity' => 'required|numeric|min:0.01',
                'reference_type' => 'required|string',
                'reference_id' => 'required|uuid',
                'notes' => 'nullable|string',
            ]);

            $allocation = $this->stockService->allocate($request->all());

            return $this->created($allocation, 'Stock allocated successfully');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to allocate stock: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/inventory/stock/release",
     *     summary="Release reserved or allocated stock",
     *     description="Release reserved or allocated stock back to available stock (e.g., when an order is cancelled)",
     *     operationId="stockRelease",
     *     tags={"Inventory-Stock"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Stock release data",
     *         @OA\JsonContent(ref="#/components/schemas/StockReleaseRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock released successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock released successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/StockTransaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid release operation",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function release(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|uuid',
                'warehouse_id' => 'required|uuid',
                'location_id' => 'nullable|uuid',
                'quantity' => 'required|numeric|min:0.01',
                'release_type' => 'required|in:reserved,allocated',
                'reference_type' => 'required|string',
                'reference_id' => 'required|uuid',
                'notes' => 'nullable|string',
            ]);

            $release = $this->stockService->release($request->all());

            return $this->success($release, 'Stock released successfully');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to release stock: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/stock/valuation",
     *     summary="Calculate stock valuation",
     *     description="Calculate total stock value for a warehouse or all warehouses based on average cost",
     *     operationId="stockValuation",
     *     tags={"Inventory-Stock"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="warehouse_id",
     *         in="query",
     *         description="Warehouse ID (optional - calculates for all warehouses if not provided)",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440005")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock valuation calculated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="warehouse_id", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440005", description="Warehouse ID (null if all warehouses)"),
     *                 @OA\Property(property="total_value", type="number", format="decimal", example=250000.00, description="Total value of stock"),
     *                 @OA\Property(property="currency", type="string", example="USD", description="Currency code")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function valuation(Request $request): JsonResponse
    {
        try {
            $warehouseId = $request->input('warehouse_id');
            $stockValue = $this->stockService->calculateStockValue($warehouseId);

            return $this->success([
                'warehouse_id' => $warehouseId,
                'total_value' => $stockValue,
                'currency' => config('app.currency', 'USD'),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to calculate stock valuation: '.$e->getMessage(), 500);
        }
    }
}
