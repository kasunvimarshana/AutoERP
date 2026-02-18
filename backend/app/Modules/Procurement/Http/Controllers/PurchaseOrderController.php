<?php

namespace App\Modules\Procurement\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Procurement\DTOs\PurchaseOrderDTO;
use App\Modules\Procurement\DTOs\PurchaseOrderReceiptDTO;
use App\Modules\Procurement\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Purchase Order API Controller
 *
 * Handles HTTP requests for purchase order management.
 */
class PurchaseOrderController extends BaseController
{
    public function __construct(
        protected PurchaseOrderService $purchaseOrderService
    ) {}

    /**
     * Display a listing of purchase orders
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $purchaseOrders = $this->purchaseOrderService->getAllPurchaseOrders($perPage);

        return $this->successResponse($purchaseOrders, 'Purchase orders retrieved successfully');
    }

    /**
     * Store a newly created purchase order
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'warehouse_id' => 'required|integer',
            'po_number' => 'required|string|unique:purchase_orders,po_number',
            'po_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:po_date',
            'status' => 'nullable|in:draft,pending',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.total_amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        $dto = PurchaseOrderDTO::fromArray($validated);
        $purchaseOrder = $this->purchaseOrderService->createPurchaseOrder($dto);

        return $this->createdResponse($purchaseOrder, 'Purchase order created successfully');
    }

    /**
     * Display the specified purchase order
     */
    public function show(int $id): JsonResponse
    {
        $purchaseOrder = $this->purchaseOrderService->getPurchaseOrderById($id);

        return $this->successResponse($purchaseOrder, 'Purchase order retrieved successfully');
    }

    /**
     * Update the specified purchase order
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'warehouse_id' => 'required|integer',
            'po_number' => 'required|string|unique:purchase_orders,po_number,'.$id,
            'po_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:po_date',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'nullable|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.total_amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        $dto = PurchaseOrderDTO::fromArray($validated);
        $purchaseOrder = $this->purchaseOrderService->updatePurchaseOrder($id, $dto);

        return $this->successResponse($purchaseOrder, 'Purchase order updated successfully');
    }

    /**
     * Remove the specified purchase order
     */
    public function destroy(int $id): JsonResponse
    {
        $this->purchaseOrderService->deletePurchaseOrder($id);

        return $this->successResponse(null, 'Purchase order deleted successfully');
    }

    /**
     * Approve a purchase order
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $purchaseOrder = $this->purchaseOrderService->approve($id);

            return $this->successResponse($purchaseOrder, 'Purchase order approved successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * Receive goods from a purchase order
     */
    public function receive(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'received_items' => 'required|array|min:1',
            'received_items.*.purchase_order_item_id' => 'required|integer',
            'received_items.*.received_quantity' => 'required|numeric|min:0.01',
            'received_items.*.batch_number' => 'nullable|string',
            'received_items.*.lot_number' => 'nullable|string',
            'received_items.*.serial_number' => 'nullable|string',
            'received_items.*.expiry_date' => 'nullable|date',
            'received_by' => 'nullable|integer',
            'received_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $dto = new PurchaseOrderReceiptDTO(
                purchaseOrderId: $id,
                receivedItems: $validated['received_items'],
                receivedBy: $validated['received_by'] ?? null,
                receivedAt: $validated['received_at'] ?? null,
                notes: $validated['notes'] ?? null
            );

            $purchaseOrder = $this->purchaseOrderService->receive($id, $dto);

            return $this->successResponse($purchaseOrder, 'Goods received successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * Cancel a purchase order
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $purchaseOrder = $this->purchaseOrderService->cancel($id);

            return $this->successResponse($purchaseOrder, 'Purchase order cancelled successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * Search purchase orders
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->input('q', '');
        $purchaseOrders = $this->purchaseOrderService->searchPurchaseOrders($search);

        return $this->successResponse($purchaseOrders, 'Search results retrieved successfully');
    }

    /**
     * Get purchase orders pending approval
     */
    public function pending(): JsonResponse
    {
        $purchaseOrders = $this->purchaseOrderService->getPendingPurchaseOrders();

        return $this->successResponse($purchaseOrders, 'Pending purchase orders retrieved successfully');
    }
}
