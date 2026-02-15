<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Procurement\PurchaseReceiptResource;
use App\Modules\Procurement\Services\ProcurementService;
use App\Http\Requests\Procurement\StorePurchaseReceiptRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PurchaseReceiptController extends BaseController
{
    protected ProcurementService $procurementService;

    public function __construct(ProcurementService $procurementService)
    {
        $this->procurementService = $procurementService;
    }

    /**
     * Display a listing of purchase receipts.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $filters = $request->only(['status', 'quality_check_status', 'vendor_id', 'purchase_order_id']);
            
            $receipts = $this->procurementService->purchaseReceiptRepository->paginate($perPage, $filters);

            return $this->paginatedResponse(
                PurchaseReceiptResource::collection($receipts),
                'Purchase receipts retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve purchase receipts: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created purchase receipt.
     */
    public function store(StorePurchaseReceiptRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['tenant_id'] = auth()->user()->tenant_id;
            $validated['created_by'] = auth()->id();

            $receipt = $this->procurementService->createPurchaseReceipt($validated);

            return $this->successResponse(
                new PurchaseReceiptResource($receipt),
                'Purchase receipt created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create purchase receipt: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase receipt.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $receipt = $this->procurementService->purchaseReceiptRepository->findById($id);

            if (!$receipt) {
                return $this->errorResponse('Purchase receipt not found', [], 404);
            }

            $this->authorize('view', $receipt);

            return $this->successResponse(
                new PurchaseReceiptResource($receipt->load(['purchaseOrder', 'vendor', 'items.product'])),
                'Purchase receipt retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve purchase receipt: ' . $e->getMessage());
        }
    }

    /**
     * Accept purchase receipt.
     */
    public function accept(int $id): JsonResponse
    {
        try {
            $receipt = $this->procurementService->acceptPurchaseReceipt($id);

            return $this->successResponse(
                new PurchaseReceiptResource($receipt),
                'Purchase receipt accepted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to accept purchase receipt: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified purchase receipt.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $receipt = $this->procurementService->purchaseReceiptRepository->findById($id);

            if (!$receipt) {
                return $this->errorResponse('Purchase receipt not found', [], 404);
            }

            $this->authorize('delete', $receipt);

            // Can only delete draft receipts
            if ($receipt->status !== 'draft') {
                return $this->errorResponse('Can only delete draft purchase receipts', [], 400);
            }

            $this->procurementService->purchaseReceiptRepository->delete($id);

            return $this->successResponse(
                null,
                'Purchase receipt deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete purchase receipt: ' . $e->getMessage());
        }
    }
}
