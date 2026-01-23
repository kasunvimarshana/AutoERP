<?php

namespace App\Modules\InventoryManagement\Http\Controllers;

use App\Core\Base\BaseController;
use OpenApi\Attributes as OA;
use App\Modules\InventoryManagement\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends BaseController
{
    protected PurchaseOrderService $purchaseOrderService;

    public function __construct(PurchaseOrderService $purchaseOrderService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
    }

    /**
     * Display a listing of purchase orders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'supplier_id' => $request->input('supplier_id'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'per_page' => $request->input('per_page', 15),
            ];

            $purchaseOrders = $this->purchaseOrderService->search($criteria);

            return $this->success($purchaseOrders);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created purchase order
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $data['tenant_id'] = $request->user()->tenant_id ?? null;

            $purchaseOrder = $this->purchaseOrderService->create($data);

            return $this->created($purchaseOrder, 'Purchase order created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified purchase order
     */
    public function show(int $id): JsonResponse
    {
        try {
            $purchaseOrder = $this->purchaseOrderService->findByIdOrFail($id);
            $purchaseOrder->load(['supplier', 'items']);

            return $this->success($purchaseOrder);
        } catch (\Exception $e) {
            return $this->notFound('Purchase order not found');
        }
    }

    /**
     * Update the specified purchase order
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $purchaseOrder = $this->purchaseOrderService->update($id, $request->all());

            return $this->success($purchaseOrder, 'Purchase order updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified purchase order
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->purchaseOrderService->delete($id);

            return $this->success(null, 'Purchase order deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Approve a purchase order
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $purchaseOrder = $this->purchaseOrderService->approve($id);

            return $this->success($purchaseOrder, 'Purchase order approved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Receive items for a purchase order
     */
    public function receive(Request $request, int $id): JsonResponse
    {
        try {
            $purchaseOrder = $this->purchaseOrderService->receive($id, $request->input('items'));

            return $this->success($purchaseOrder, 'Purchase order received successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
