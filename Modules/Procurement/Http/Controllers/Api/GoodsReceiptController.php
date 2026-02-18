<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Procurement\Http\Requests\StoreGoodsReceiptRequest;
use Modules\Procurement\Http\Requests\UpdateGoodsReceiptRequest;
use Modules\Procurement\Http\Resources\GoodsReceiptResource;
use Modules\Procurement\Services\GoodsReceiptService;

/**
 * Goods Receipt API Controller
 *
 * Handles API requests for goods receipt management.
 */
class GoodsReceiptController extends Controller
{
    /**
     * GoodsReceiptController constructor.
     */
    public function __construct(
        protected GoodsReceiptService $goodsReceiptService
    ) {}

    /**
     * Display a listing of goods receipts.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $goodsReceipts = $this->goodsReceiptService->getPaginated($perPage);

        return GoodsReceiptResource::collection($goodsReceipts)->response();
    }

    /**
     * Store a newly created goods receipt.
     */
    public function store(StoreGoodsReceiptRequest $request): JsonResponse
    {
        $goodsReceipt = $this->goodsReceiptService->create($request->validated());

        return (new GoodsReceiptResource($goodsReceipt))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified goods receipt.
     */
    public function show(int $id): JsonResponse
    {
        $goodsReceipt = $this->goodsReceiptService->findById($id);

        if (! $goodsReceipt) {
            return response()->json([
                'message' => 'Goods receipt not found',
            ], 404);
        }

        return (new GoodsReceiptResource($goodsReceipt))->response();
    }

    /**
     * Update the specified goods receipt.
     */
    public function update(UpdateGoodsReceiptRequest $request, int $id): JsonResponse
    {
        $goodsReceipt = $this->goodsReceiptService->update($id, $request->validated());

        return (new GoodsReceiptResource($goodsReceipt))->response();
    }

    /**
     * Remove the specified goods receipt.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->goodsReceiptService->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Goods receipt not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Goods receipt deleted successfully',
        ], 200);
    }

    /**
     * Confirm a goods receipt.
     */
    public function confirm(int $id): JsonResponse
    {
        $goodsReceipt = $this->goodsReceiptService->confirm($id);

        return (new GoodsReceiptResource($goodsReceipt))->response();
    }

    /**
     * Perform 3-way matching.
     */
    public function match(int $id): JsonResponse
    {
        $matched = $this->goodsReceiptService->performThreeWayMatch($id);

        return response()->json([
            'message' => $matched ? '3-way match successful' : '3-way match partially complete',
            'matched' => $matched,
        ]);
    }

    /**
     * Get receipts by purchase order.
     */
    public function byPurchaseOrder(int $purchaseOrderId): JsonResponse
    {
        $goodsReceipts = $this->goodsReceiptService->getReceiptsByPurchaseOrder($purchaseOrderId);

        return GoodsReceiptResource::collection($goodsReceipts)->response();
    }

    /**
     * Search goods receipts.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        $goodsReceipts = $this->goodsReceiptService->search($query);

        return GoodsReceiptResource::collection($goodsReceipts)->response();
    }
}
