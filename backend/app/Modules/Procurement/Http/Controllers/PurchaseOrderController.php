<?php

namespace App\Modules\Procurement\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Procurement\Services\PurchaseOrderService;
use App\Modules\Procurement\Requests\CreatePurchaseOrderRequest;
use App\Modules\Procurement\Requests\UpdatePurchaseOrderRequest;
use App\Modules\Procurement\Resources\PurchaseOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Purchase Order Controller
 * 
 * Handles CRUD operations for purchase order management
 * 
 * @package App\Modules\Procurement\Http\Controllers
 */
class PurchaseOrderController extends BaseController
{
    protected PurchaseOrderService $service;

    public function __construct(PurchaseOrderService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of purchase orders
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $purchaseOrders = $this->service->getAllPurchaseOrders();
        return $this->collection(PurchaseOrderResource::collection($purchaseOrders));
    }

    /**
     * Store a newly created purchase order
     *
     * @param CreatePurchaseOrderRequest $request
     * @return JsonResponse
     */
    public function store(CreatePurchaseOrderRequest $request): JsonResponse
    {
        $purchaseOrder = $this->service->createPurchaseOrder($request->validated());
        return $this->created(new PurchaseOrderResource($purchaseOrder));
    }

    /**
     * Display the specified purchase order
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $purchaseOrder = $this->service->getPurchaseOrder($id);
        
        if (!$purchaseOrder) {
            return $this->notFound();
        }
        
        return $this->resource(new PurchaseOrderResource($purchaseOrder));
    }

    /**
     * Update the specified purchase order
     *
     * @param UpdatePurchaseOrderRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdatePurchaseOrderRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updatePurchaseOrder($id, $request->validated());
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->success(null, 'Purchase Order updated successfully');
    }

    /**
     * Remove the specified purchase order
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deletePurchaseOrder($id);
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->noContent();
    }

    /**
     * Approve a purchase order
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->service->approvePurchaseOrder($id, $userId);
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->success(null, 'Purchase Order approved successfully');
    }
}
