<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Procurement\Http\Requests\StorePurchaseRequisitionLineRequest;
use Modules\Procurement\Http\Requests\UpdatePurchaseRequisitionLineRequest;
use Modules\Procurement\Http\Resources\PurchaseRequisitionLineResource;
use Modules\Procurement\Repositories\Contracts\PurchaseRequisitionLineRepositoryInterface;

/**
 * Purchase Requisition Line API Controller
 *
 * Handles API requests for purchase requisition line management.
 */
class PurchaseRequisitionLineController extends Controller
{
    /**
     * PurchaseRequisitionLineController constructor.
     */
    public function __construct(
        protected PurchaseRequisitionLineRepositoryInterface $lineRepository
    ) {}

    /**
     * Display a listing of purchase requisition lines.
     */
    public function index(Request $request): JsonResponse
    {
        $lines = $this->lineRepository->all();

        return PurchaseRequisitionLineResource::collection($lines)->response();
    }

    /**
     * Store a newly created purchase requisition line.
     */
    public function store(StorePurchaseRequisitionLineRequest $request): JsonResponse
    {
        $line = $this->lineRepository->create($request->validated());

        return (new PurchaseRequisitionLineResource($line))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified purchase requisition line.
     */
    public function show(int $id): JsonResponse
    {
        $line = $this->lineRepository->findById($id);

        if (! $line) {
            return response()->json([
                'message' => 'Purchase requisition line not found',
            ], 404);
        }

        return (new PurchaseRequisitionLineResource($line))->response();
    }

    /**
     * Update the specified purchase requisition line.
     */
    public function update(UpdatePurchaseRequisitionLineRequest $request, int $id): JsonResponse
    {
        $line = $this->lineRepository->update($id, $request->validated());

        return (new PurchaseRequisitionLineResource($line))->response();
    }

    /**
     * Remove the specified purchase requisition line.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->lineRepository->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Purchase requisition line not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Purchase requisition line deleted successfully',
        ], 200);
    }

    /**
     * Get lines by requisition.
     */
    public function byRequisition(int $requisitionId): JsonResponse
    {
        $lines = $this->lineRepository->getLinesByRequisition($requisitionId);

        return PurchaseRequisitionLineResource::collection($lines)->response();
    }
}
