<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Procurement\PurchaseOrderResource;
use App\Modules\Procurement\Services\ProcurementService;
use App\Http\Requests\Procurement\StorePurchaseOrderRequest;
use App\Http\Requests\Procurement\UpdatePurchaseOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PurchaseOrderController extends BaseController
{
    protected ProcurementService $procurementService;

    public function __construct(ProcurementService $procurementService)
    {
        $this->procurementService = $procurementService;
    }

    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $filters = $request->only(['status', 'approval_status', 'payment_status', 'vendor_id']);
            
            $purchaseOrders = $this->procurementService->purchaseOrderRepository->paginate($perPage, $filters);

            return $this->paginatedResponse(
                PurchaseOrderResource::collection($purchaseOrders),
                'Purchase orders retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve purchase orders: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['tenant_id'] = auth()->user()->tenant_id;
            $validated['organization_id'] = auth()->user()->organization_id ?? null;
            $validated['branch_id'] = auth()->user()->branch_id ?? null;
            $validated['created_by'] = auth()->id();

            $purchaseOrder = $this->procurementService->createPurchaseOrder($validated);

            return $this->successResponse(
                new PurchaseOrderResource($purchaseOrder),
                'Purchase order created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase order.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $purchaseOrder = $this->procurementService->purchaseOrderRepository->findById($id);

            if (!$purchaseOrder) {
                return $this->errorResponse('Purchase order not found', [], 404);
            }

            $this->authorize('view', $purchaseOrder);

            return $this->successResponse(
                new PurchaseOrderResource($purchaseOrder->load(['vendor', 'items.product', 'receipts'])),
                'Purchase order retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified purchase order.
     */
    public function update(UpdatePurchaseOrderRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = auth()->id();

            $purchaseOrder = $this->procurementService->updatePurchaseOrder($id, $validated);

            return $this->successResponse(
                new PurchaseOrderResource($purchaseOrder),
                'Purchase order updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Approve purchase order.
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $purchaseOrder = $this->procurementService->approvePurchaseOrder($id);

            return $this->successResponse(
                new PurchaseOrderResource($purchaseOrder),
                'Purchase order approved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to approve purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Reject purchase order.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $reason = $request->input('reason');
            $purchaseOrder = $this->procurementService->rejectPurchaseOrder($id, $reason);

            return $this->successResponse(
                new PurchaseOrderResource($purchaseOrder),
                'Purchase order rejected successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reject purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Cancel purchase order.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $reason = $request->input('reason');
            $purchaseOrder = $this->procurementService->cancelPurchaseOrder($id, $reason);

            return $this->successResponse(
                new PurchaseOrderResource($purchaseOrder),
                'Purchase order cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel purchase order: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified purchase order.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $purchaseOrder = $this->procurementService->purchaseOrderRepository->findById($id);

            if (!$purchaseOrder) {
                return $this->errorResponse('Purchase order not found', [], 404);
            }

            $this->authorize('delete', $purchaseOrder);

            // Can only delete draft purchase orders
            if ($purchaseOrder->status !== 'draft') {
                return $this->errorResponse('Can only delete draft purchase orders', [], 400);
            }

            $this->procurementService->purchaseOrderRepository->delete($id);

            return $this->successResponse(
                null,
                'Purchase order deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete purchase order: ' . $e->getMessage());
        }
    }
}
