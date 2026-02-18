<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Inventory\Http\Requests\StoreWarehouseRequest;
use Modules\Inventory\Http\Requests\UpdateWarehouseRequest;
use Modules\Inventory\Repositories\WarehouseRepository;

/**
 * Warehouse Controller
 *
 * Handles HTTP requests for warehouse management.
 */
class WarehouseController extends BaseController
{
    /**
     * Constructor
     */
    public function __construct(
        private WarehouseRepository $warehouseRepository
    ) {}

    /**
     * @OA\Get(
     *     path="/api/inventory/warehouses",
     *     summary="List all warehouses",
     *     description="Retrieve paginated list of all warehouses with filtering and search capabilities",
     *     operationId="warehousesIndex",
     *     tags={"Inventory-Warehouses"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by warehouse name, code, or city",
     *         required=false,
     *         @OA\Schema(type="string", example="main")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by active status",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="country",
     *         in="query",
     *         description="Filter by country",
     *         required=false,
     *         @OA\Schema(type="string", example="USA")
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
     *         description="Warehouses retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Warehouse")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string"),
     *                 @OA\Property(property="last", type="string"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", nullable=true)
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
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
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 15);
            $query = $this->warehouseRepository->query();

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('country')) {
                $query->where('country', $request->input('country'));
            }

            $warehouses = $query->with(['locations'])->paginate($perPage);

            return $this->success($warehouses);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch warehouses: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/inventory/warehouses",
     *     summary="Create a new warehouse",
     *     description="Create a new warehouse with location and contact information",
     *     operationId="warehousesStore",
     *     tags={"Inventory-Warehouses"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Warehouse creation data",
     *         @OA\JsonContent(ref="#/components/schemas/StoreWarehouseRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Warehouse created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Warehouse created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Warehouse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid data",
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
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        try {
            $warehouse = $this->warehouseRepository->create($request->validated());

            return $this->created($warehouse, 'Warehouse created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to create warehouse: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/warehouses/{id}",
     *     summary="Get warehouse details",
     *     description="Retrieve detailed information about a specific warehouse including locations and stock levels",
     *     operationId="warehousesShow",
     *     tags={"Inventory-Warehouses"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Warehouse ID (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Warehouse details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Warehouse")
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
     *         response=404,
     *         description="Warehouse not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $warehouse = $this->warehouseRepository->query()
                ->with(['locations', 'stockLevels'])
                ->findOrFail($id);

            return $this->success($warehouse);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Warehouse not found');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch warehouse: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/inventory/warehouses/{id}",
     *     summary="Update a warehouse",
     *     description="Update an existing warehouse's information",
     *     operationId="warehousesUpdate",
     *     tags={"Inventory-Warehouses"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Warehouse ID (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Warehouse update data",
     *         @OA\JsonContent(ref="#/components/schemas/UpdateWarehouseRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Warehouse updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Warehouse updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Warehouse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid data",
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
     *         response=404,
     *         description="Warehouse not found",
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
    public function update(UpdateWarehouseRequest $request, string $id): JsonResponse
    {
        try {
            $warehouse = $this->warehouseRepository->findOrFail($id);
            $warehouse->update($request->validated());

            return $this->updated($warehouse, 'Warehouse updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Warehouse not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update warehouse: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/inventory/warehouses/{id}",
     *     summary="Delete a warehouse",
     *     description="Delete a warehouse from the system. Warehouses with existing stock cannot be deleted.",
     *     operationId="warehousesDestroy",
     *     tags={"Inventory-Warehouses"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Warehouse ID (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Warehouse deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Warehouse has stock",
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
     *         response=404,
     *         description="Warehouse not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $warehouse = $this->warehouseRepository->findOrFail($id);

            // Check if warehouse has stock
            if ($warehouse->stockLevels()->exists()) {
                return $this->error('Cannot delete warehouse with existing stock', 400);
            }

            $this->warehouseRepository->delete($id);

            return $this->deleted('Warehouse deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Warehouse not found');
        } catch (\Exception $e) {
            return $this->error('Failed to delete warehouse: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/warehouses/{id}/stock-summary",
     *     summary="Get warehouse stock summary",
     *     description="Retrieve stock summary for a warehouse including total products, quantities, and value",
     *     operationId="warehousesStockSummary",
     *     tags={"Inventory-Warehouses"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Warehouse ID (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440005")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock summary retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_products", type="integer", example=150, description="Total number of unique products"),
     *                 @OA\Property(property="total_quantity", type="number", format="decimal", example=5000.00, description="Total quantity of all products"),
     *                 @OA\Property(property="total_value", type="number", format="decimal", example=250000.00, description="Total value of inventory"),
     *                 @OA\Property(property="low_stock_products", type="integer", example=10, description="Number of products below reorder level")
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
     *         response=404,
     *         description="Warehouse not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function stockSummary(string $id): JsonResponse
    {
        try {
            $warehouse = $this->warehouseRepository->findOrFail($id);

            $stockLevels = $warehouse->stockLevels()
                ->with('product')
                ->get();

            $summary = [
                'total_products' => $stockLevels->count(),
                'total_quantity' => $stockLevels->sum('quantity_available'),
                'total_value' => $stockLevels->sum(function ($level) {
                    return $level->quantity_available * ($level->product->average_cost ?? 0);
                }),
                'low_stock_products' => $stockLevels->filter(function ($level) {
                    return $level->product->reorder_level &&
                           $level->quantity_available <= $level->product->reorder_level;
                })->count(),
            ];

            return $this->success($summary);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Warehouse not found');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch stock summary: '.$e->getMessage(), 500);
        }
    }
}
