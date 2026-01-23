<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Requests\ReceiveItemsRequest;
use Modules\Inventory\Requests\StorePurchaseOrderRequest;
use Modules\Inventory\Requests\UpdatePurchaseOrderRequest;
use Modules\Inventory\Resources\PurchaseOrderResource;
use Modules\Inventory\Services\PurchaseOrderService;

/**
 * Purchase Order Controller
 *
 * Handles HTTP requests for Purchase Order operations
 */
class PurchaseOrderController extends Controller
{
    /**
     * PurchaseOrderController constructor
     */
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrderService
    ) {}

    /**
     * Display a listing of purchase orders
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'supplier_id', 'branch_id', 'from_date', 'to_date', 'paginate', 'per_page']);

        $pos = $this->purchaseOrderService->getAll($filters);

        return response()->json([
            'success' => true,
            'message' => 'Purchase orders retrieved successfully',
            'data' => PurchaseOrderResource::collection($pos),
        ]);
    }

    /**
     * Store a newly created purchase order
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $po = $this->purchaseOrderService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Purchase order created successfully',
            'data' => new PurchaseOrderResource($po),
        ], 201);
    }

    /**
     * Display the specified purchase order
     */
    public function show(int $id): JsonResponse
    {
        $po = $this->purchaseOrderService->getById($id);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order retrieved successfully',
            'data' => new PurchaseOrderResource($po),
        ]);
    }

    /**
     * Update the specified purchase order
     */
    public function update(UpdatePurchaseOrderRequest $request, int $id): JsonResponse
    {
        $po = $this->purchaseOrderService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Purchase order updated successfully',
            'data' => new PurchaseOrderResource($po),
        ]);
    }

    /**
     * Remove the specified purchase order
     */
    public function destroy(int $id): JsonResponse
    {
        $this->purchaseOrderService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order deleted successfully',
        ]);
    }

    /**
     * Approve a purchase order
     */
    public function approve(int $id): JsonResponse
    {
        $po = $this->purchaseOrderService->approve($id);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order approved successfully',
            'data' => new PurchaseOrderResource($po),
        ]);
    }

    /**
     * Receive items from purchase order
     */
    public function receiveItems(ReceiveItemsRequest $request, int $id): JsonResponse
    {
        $po = $this->purchaseOrderService->receiveItems($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Items received successfully',
            'data' => new PurchaseOrderResource($po),
        ]);
    }

    /**
     * Search purchase orders
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'supplier_id', 'branch_id', 'from_date', 'to_date']);
        $pos = $this->purchaseOrderService->search($filters);

        return response()->json([
            'success' => true,
            'message' => 'Purchase orders retrieved successfully',
            'data' => PurchaseOrderResource::collection($pos),
        ]);
    }
}
