<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Procurement\Http\Requests\StorePurchaseOrderLineRequest;
use Modules\Procurement\Http\Requests\UpdatePurchaseOrderLineRequest;
use Modules\Procurement\Http\Resources\PurchaseOrderLineResource;
use Modules\Procurement\Repositories\Contracts\PurchaseOrderLineRepositoryInterface;

/**
 * Purchase Order Line API Controller
 *
 * Handles API requests for purchase order line management.
 */
class PurchaseOrderLineController extends Controller
{
    /**
     * PurchaseOrderLineController constructor.
     */
    public function __construct(
        protected PurchaseOrderLineRepositoryInterface $lineRepository
    ) {}

    /**
     * Display a listing of purchase order lines.
     */
    public function index(Request $request): JsonResponse
    {
        $lines = $this->lineRepository->all();

        return PurchaseOrderLineResource::collection($lines)->response();
    }

    /**
     * Store a newly created purchase order line.
     */
    public function store(StorePurchaseOrderLineRequest $request): JsonResponse
    {
        $line = $this->lineRepository->create($request->validated());

        return (new PurchaseOrderLineResource($line))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified purchase order line.
     */
    public function show(int $id): JsonResponse
    {
        $line = $this->lineRepository->findById($id);

        if (! $line) {
            return response()->json([
                'message' => 'Purchase order line not found',
            ], 404);
        }

        return (new PurchaseOrderLineResource($line))->response();
    }

    /**
     * Update the specified purchase order line.
     */
    public function update(UpdatePurchaseOrderLineRequest $request, int $id): JsonResponse
    {
        $line = $this->lineRepository->update($id, $request->validated());

        return (new PurchaseOrderLineResource($line))->response();
    }

    /**
     * Remove the specified purchase order line.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->lineRepository->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Purchase order line not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Purchase order line deleted successfully',
        ], 200);
    }

    /**
     * Get lines by purchase order.
     */
    public function byOrder(int $orderId): JsonResponse
    {
        $lines = $this->lineRepository->getLinesByOrder($orderId);

        return PurchaseOrderLineResource::collection($lines)->response();
    }
}
