<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Procurement\Http\Requests\StorePurchaseOrderRequest;
use Modules\Procurement\Http\Requests\UpdatePurchaseOrderRequest;
use Modules\Procurement\Http\Resources\PurchaseOrderResource;
use Modules\Procurement\Services\PurchaseOrderService;

/**
 * Purchase Order API Controller
 *
 * Handles API requests for purchase order management.
 */
class PurchaseOrderController extends Controller
{
    /**
     * PurchaseOrderController constructor.
     */
    public function __construct(
        protected PurchaseOrderService $purchaseOrderService
    ) {}

    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $purchaseOrders = $this->purchaseOrderService->getPaginated($perPage);

        return PurchaseOrderResource::collection($purchaseOrders)->response();
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        if (! empty($lines)) {
            $purchaseOrder = $this->purchaseOrderService->createWithLines($data, $lines);
        } else {
            $purchaseOrder = $this->purchaseOrderService->create($data);
        }

        return (new PurchaseOrderResource($purchaseOrder->load('lines')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified purchase order.
     */
    public function show(int $id): JsonResponse
    {
        $purchaseOrder = $this->purchaseOrderService->findById($id, true);

        if (! $purchaseOrder) {
            return response()->json([
                'message' => 'Purchase order not found',
            ], 404);
        }

        return (new PurchaseOrderResource($purchaseOrder))->response();
    }

    /**
     * Update the specified purchase order.
     */
    public function update(UpdatePurchaseOrderRequest $request, int $id): JsonResponse
    {
        $purchaseOrder = $this->purchaseOrderService->update($id, $request->validated());

        return (new PurchaseOrderResource($purchaseOrder->load('lines')))->response();
    }

    /**
     * Remove the specified purchase order.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->purchaseOrderService->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Purchase order not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Purchase order deleted successfully',
        ], 200);
    }

    /**
     * Send a purchase order.
     */
    public function send(int $id): JsonResponse
    {
        $purchaseOrder = $this->purchaseOrderService->send($id);

        return (new PurchaseOrderResource($purchaseOrder))->response();
    }

    /**
     * Confirm a purchase order.
     */
    public function confirm(int $id): JsonResponse
    {
        $purchaseOrder = $this->purchaseOrderService->confirm($id);

        return (new PurchaseOrderResource($purchaseOrder))->response();
    }

    /**
     * Close a purchase order.
     */
    public function close(int $id): JsonResponse
    {
        $purchaseOrder = $this->purchaseOrderService->close($id);

        return (new PurchaseOrderResource($purchaseOrder))->response();
    }

    /**
     * Create purchase order from requisition.
     */
    public function createFromRequisition(Request $request): JsonResponse
    {
        $request->validate([
            'requisition_id' => ['required', 'integer', 'exists:purchase_requisitions,id'],
            'expected_delivery_date' => ['nullable', 'date'],
        ]);

        $requisitionId = $request->input('requisition_id');
        $additionalData = $request->only(['expected_delivery_date', 'notes', 'payment_terms']);

        $purchaseOrder = $this->purchaseOrderService->createFromRequisition($requisitionId, $additionalData);

        return (new PurchaseOrderResource($purchaseOrder))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Search purchase orders.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        $purchaseOrders = $this->purchaseOrderService->search($query);

        return PurchaseOrderResource::collection($purchaseOrders)->response();
    }

    /**
     * Get purchase orders by status.
     */
    public function byStatus(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $purchaseOrders = $this->purchaseOrderService->getOrdersByStatus($status);

        return PurchaseOrderResource::collection($purchaseOrders)->response();
    }
}
