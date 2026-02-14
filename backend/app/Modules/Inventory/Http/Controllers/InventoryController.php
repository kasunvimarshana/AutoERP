<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Base\BaseController;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Inventory\DTOs\AdjustmentDTO;
use App\Modules\Inventory\DTOs\TransferDTO;
use App\Modules\Inventory\Http\Requests\AdjustStockRequest;
use App\Modules\Inventory\Http\Requests\TransferStockRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Inventory",
 *     description="Inventory management endpoints"
 * )
 */
class InventoryController extends BaseController
{
    protected InventoryService $service;

    public function __construct(InventoryService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/inventory",
     *     tags={"Inventory"},
     *     summary="List all inventory items",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="branch_id",
     *         in="query",
     *         description="Filter by branch ID",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Inventory retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id');
            
            if ($branchId) {
                $inventory = $this->service->getInventoryByBranch($branchId);
                return $this->successResponse($inventory, 'Inventory retrieved successfully');
            }
            
            $perPage = $request->get('per_page', 15);
            $inventory = $this->service->paginate($perPage, ['productVariant.product', 'branch']);
            
            return $this->successResponse($inventory, 'Inventory retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/inventory/{id}",
     *     tags={"Inventory"},
     *     summary="Get a specific inventory item",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Inventory item retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Inventory")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Inventory item not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $inventory = $this->service->findById($id, ['productVariant.product', 'branch', 'stockLedger']);
            
            if (!$inventory) {
                return $this->errorResponse('Inventory item not found', 404);
            }
            
            return $this->successResponse($inventory, 'Inventory item retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/inventory/{id}/ledger",
     *     tags={"Inventory"},
     *     summary="Get stock ledger for an inventory item",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock ledger retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/StockLedger"))
     *         )
     *     )
     * )
     */
    public function ledger(string $id): JsonResponse
    {
        try {
            $inventory = $this->service->findById($id);
            
            if (!$inventory) {
                return $this->errorResponse('Inventory item not found', 404);
            }
            
            $ledger = $this->service->getStockLedger(
                $inventory->product_variant_id,
                $inventory->branch_id
            );
            
            return $this->successResponse($ledger, 'Stock ledger retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/inventory/adjust",
     *     tags={"Inventory"},
     *     summary="Adjust stock levels",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_id", "branch_id", "product_variant_id", "quantity", "unit_cost"},
     *             @OA\Property(property="tenant_id", type="string", format="uuid"),
     *             @OA\Property(property="branch_id", type="string", format="uuid"),
     *             @OA\Property(property="product_variant_id", type="string", format="uuid"),
     *             @OA\Property(property="quantity", type="integer", example=10),
     *             @OA\Property(property="unit_cost", type="number", format="float", example=10.50),
     *             @OA\Property(property="reference_type", type="string", example="adjustment"),
     *             @OA\Property(property="reference_id", type="string"),
     *             @OA\Property(property="batch_number", type="string"),
     *             @OA\Property(property="lot_number", type="string"),
     *             @OA\Property(property="expiry_date", type="string", format="date"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="created_by", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Stock adjusted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock adjusted successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/StockLedger")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function adjust(AdjustStockRequest $request): JsonResponse
    {
        try {
            $dto = AdjustmentDTO::fromArray($request->validated());
            $ledgerEntry = $this->service->adjustStock($dto);
            
            return $this->successResponse($ledgerEntry, 'Stock adjusted successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/inventory/transfer",
     *     tags={"Inventory"},
     *     summary="Transfer stock between branches",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_id", "from_branch_id", "to_branch_id", "product_variant_id", "quantity", "unit_cost"},
     *             @OA\Property(property="tenant_id", type="string", format="uuid"),
     *             @OA\Property(property="from_branch_id", type="string", format="uuid"),
     *             @OA\Property(property="to_branch_id", type="string", format="uuid"),
     *             @OA\Property(property="product_variant_id", type="string", format="uuid"),
     *             @OA\Property(property="quantity", type="integer", example=10),
     *             @OA\Property(property="unit_cost", type="number", format="float", example=10.50),
     *             @OA\Property(property="reference_type", type="string", example="transfer"),
     *             @OA\Property(property="reference_id", type="string"),
     *             @OA\Property(property="batch_number", type="string"),
     *             @OA\Property(property="lot_number", type="string"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="created_by", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Stock transferred successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock transferred successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function transfer(TransferStockRequest $request): JsonResponse
    {
        try {
            $dto = TransferDTO::fromArray($request->validated());
            $result = $this->service->transferStock($dto);
            
            return $this->successResponse($result, 'Stock transferred successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/inventory/low-stock",
     *     tags={"Inventory"},
     *     summary="Get low stock items for a branch",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="branch_id",
     *         in="query",
     *         description="Branch ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Low stock items retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Inventory"))
     *         )
     *     )
     * )
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $branchId = $request->get('branch_id');
            
            if (!$branchId) {
                return $this->errorResponse('Branch ID is required', 400);
            }
            
            $lowStockItems = $this->service->getLowStockItems($branchId);
            
            return $this->successResponse($lowStockItems, 'Low stock items retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
