<?php

namespace App\Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Manufacturing\DTOs\CreateProductionOrderDTO;
use App\Modules\Manufacturing\DTOs\MaterialConsumptionDTO;
use App\Modules\Manufacturing\Enums\ProductionOrderStatus;
use App\Modules\Manufacturing\Services\ProductionOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Production Order API Controller
 *
 * Handles HTTP requests for production order management.
 */
class ProductionOrderController extends BaseController
{
    public function __construct(
        protected ProductionOrderService $productionOrderService
    ) {}

    /**
     * Display a listing of production orders
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $productionOrders = $this->productionOrderService->getAllProductionOrders($perPage);

        return $this->successResponse($productionOrders, 'Production orders retrieved successfully');
    }

    /**
     * Store a newly created production order
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'production_order_number' => 'required|string|unique:production_orders,production_order_number',
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|numeric|min:0.0001',
            'bill_of_material_id' => 'nullable|integer|exists:bill_of_materials,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'scheduled_start_date' => 'nullable|date',
            'scheduled_end_date' => 'nullable|date|after_or_equal:scheduled_start_date',
            'status' => 'nullable|in:draft,released',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'notes' => 'nullable|string',
        ]);

        $dto = CreateProductionOrderDTO::fromArray($validated);
        $productionOrder = $this->productionOrderService->createProductionOrder($dto);

        return $this->createdResponse($productionOrder, 'Production order created successfully');
    }

    /**
     * Display the specified production order
     */
    public function show(int $id): JsonResponse
    {
        $productionOrder = $this->productionOrderService->getProductionOrderById($id);

        return $this->successResponse($productionOrder, 'Production order retrieved successfully');
    }

    /**
     * Update the specified production order
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'production_order_number' => 'required|string|unique:production_orders,production_order_number,'.$id,
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|numeric|min:0.0001',
            'bill_of_material_id' => 'nullable|integer|exists:bill_of_materials,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'scheduled_start_date' => 'nullable|date',
            'scheduled_end_date' => 'nullable|date|after_or_equal:scheduled_start_date',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'notes' => 'nullable|string',
        ]);

        $dto = CreateProductionOrderDTO::fromArray($validated);
        $productionOrder = $this->productionOrderService->updateProductionOrder($id, $dto);

        return $this->successResponse($productionOrder, 'Production order updated successfully');
    }

    /**
     * Remove the specified production order
     */
    public function destroy(int $id): JsonResponse
    {
        $this->productionOrderService->deleteProductionOrder($id);

        return $this->successResponse(null, 'Production order deleted successfully');
    }

    /**
     * Release a production order
     */
    public function release(int $id): JsonResponse
    {
        $productionOrder = $this->productionOrderService->release($id);

        return $this->successResponse($productionOrder, 'Production order released successfully');
    }

    /**
     * Start production (consume materials)
     */
    public function start(int $id): JsonResponse
    {
        $productionOrder = $this->productionOrderService->startProduction($id);

        return $this->successResponse($productionOrder, 'Production started successfully');
    }

    /**
     * Record material consumption
     */
    public function consumeMaterials(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'consumed_items' => 'required|array|min:1',
            'consumed_items.*.product_id' => 'required|integer|exists:products,id',
            'consumed_items.*.quantity' => 'required|numeric|min:0.0001',
            'consumed_items.*.batch_number' => 'nullable|string',
            'consumed_items.*.lot_number' => 'nullable|string',
            'consumed_items.*.serial_number' => 'nullable|string',
            'consumed_by' => 'nullable|integer|exists:users,id',
            'consumed_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['production_order_id'] = $id;
        $dto = MaterialConsumptionDTO::fromArray($validated);
        $productionOrder = $this->productionOrderService->consumeMaterials($dto);

        return $this->successResponse($productionOrder, 'Materials consumed successfully');
    }

    /**
     * Complete production order
     */
    public function complete(int $id): JsonResponse
    {
        $productionOrder = $this->productionOrderService->complete($id);

        return $this->successResponse($productionOrder, 'Production order completed successfully');
    }

    /**
     * Cancel production order
     */
    public function cancel(int $id): JsonResponse
    {
        $productionOrder = $this->productionOrderService->cancel($id);

        return $this->successResponse($productionOrder, 'Production order cancelled successfully');
    }

    /**
     * Get production orders by status
     */
    public function byStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,released,in_progress,completed,cancelled',
        ]);

        $status = ProductionOrderStatus::from($validated['status']);
        $productionOrders = $this->productionOrderService->getByStatus($status);

        return $this->successResponse($productionOrders, 'Production orders retrieved successfully');
    }

    /**
     * Get in-progress production orders
     */
    public function inProgress(): JsonResponse
    {
        $productionOrders = $this->productionOrderService->getInProgress();

        return $this->successResponse($productionOrders, 'In-progress production orders retrieved successfully');
    }

    /**
     * Get overdue production orders
     */
    public function overdue(): JsonResponse
    {
        $productionOrders = $this->productionOrderService->getOverdue();

        return $this->successResponse($productionOrders, 'Overdue production orders retrieved successfully');
    }

    /**
     * Search production orders
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:1',
        ]);

        $productionOrders = $this->productionOrderService->searchProductionOrders($validated['query']);

        return $this->successResponse($productionOrders, 'Search results retrieved successfully');
    }
}
