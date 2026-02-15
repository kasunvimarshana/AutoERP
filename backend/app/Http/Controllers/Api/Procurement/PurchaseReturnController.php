<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Procurement\PurchaseReturnResource;
use App\Modules\Procurement\Services\ProcurementService;
use App\Http\Requests\Procurement\StorePurchaseReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PurchaseReturnController extends BaseController
{
    protected ProcurementService $procurementService;

    public function __construct(ProcurementService $procurementService)
    {
        $this->procurementService = $procurementService;
    }

    /**
     * Display a listing of purchase returns.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $returns = $this->procurementService->getAll([], $perPage); // Would need a dedicated repository

            return $this->paginatedResponse(
                PurchaseReturnResource::collection($returns),
                'Purchase returns retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve purchase returns: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created purchase return.
     */
    public function store(StorePurchaseReturnRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['tenant_id'] = auth()->user()->tenant_id;
            $validated['created_by'] = auth()->id();

            $return = $this->procurementService->createPurchaseReturn($validated);

            return $this->successResponse(
                new PurchaseReturnResource($return),
                'Purchase return created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create purchase return: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase return.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $return = $this->procurementService->getById($id); // Would need proper method

            if (!$return) {
                return $this->errorResponse('Purchase return not found', [], 404);
            }

            $this->authorize('view', $return);

            return $this->successResponse(
                new PurchaseReturnResource($return->load(['purchaseOrder', 'purchaseReceipt', 'vendor', 'items.product'])),
                'Purchase return retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve purchase return: ' . $e->getMessage());
        }
    }

    /**
     * Approve purchase return.
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $return = $this->procurementService->approvePurchaseReturn($id);

            return $this->successResponse(
                new PurchaseReturnResource($return),
                'Purchase return approved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to approve purchase return: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified purchase return.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $return = $this->procurementService->getById($id);

            if (!$return) {
                return $this->errorResponse('Purchase return not found', [], 404);
            }

            $this->authorize('delete', $return);

            // Can only delete draft returns
            if ($return->status !== 'draft') {
                return $this->errorResponse('Can only delete draft purchase returns', [], 400);
            }

            $this->procurementService->delete($id);

            return $this->successResponse(
                null,
                'Purchase return deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete purchase return: ' . $e->getMessage());
        }
    }
}
