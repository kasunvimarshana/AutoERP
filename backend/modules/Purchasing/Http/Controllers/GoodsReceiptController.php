<?php

declare(strict_types=1);

namespace Modules\Purchasing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Purchasing\Services\GoodsReceiptService;

/**
 * Goods Receipt Controller
 *
 * Handles HTTP requests for goods receipt management.
 */
class GoodsReceiptController extends BaseController
{
    protected GoodsReceiptService $service;

    public function __construct(GoodsReceiptService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/purchasing/goods-receipts",
     *     summary="List goods receipts",
     *     description="Retrieve paginated list of goods receipts with optional filtering",
     *     operationId="goodsReceiptsIndex",
     *     tags={"Purchasing-GoodsReceipts"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "completed", "cancelled"}, example="pending")
     *     ),
     *     @OA\Parameter(
     *         name="supplier_id",
     *         in="query",
     *         description="Filter by supplier ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="warehouse_id",
     *         in="query",
     *         description="Filter by warehouse ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter by start date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2026-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter by end date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2026-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Goods receipts retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/GoodsReceipt")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'supplier_id', 'warehouse_id', 'date_from', 'date_to']);
        $receipts = $this->service->getAll($filters);

        return $this->success($receipts);
    }

    /**
     * @OA\Get(
     *     path="/api/purchasing/goods-receipts/{id}",
     *     summary="Get goods receipt details",
     *     description="Retrieve detailed information about a specific goods receipt",
     *     operationId="goodsReceiptShow",
     *     tags={"Purchasing-GoodsReceipts"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Goods receipt ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Goods receipt retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/GoodsReceipt")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Goods receipt not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function show(int $id): JsonResponse
    {
        $receipt = $this->service->find($id);

        if (! $receipt) {
            return $this->notFound('Goods receipt not found');
        }

        return $this->success($receipt);
    }

    /**
     * @OA\Post(
     *     path="/api/purchasing/goods-receipts",
     *     summary="Create goods receipt",
     *     description="Create a new goods receipt from a purchase order",
     *     operationId="goodsReceiptStore",
     *     tags={"Purchasing-GoodsReceipts"},
     *     security={{"sanctum_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Goods receipt data",
     *         @OA\JsonContent(ref="#/components/schemas/GoodsReceiptRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Goods receipt created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Goods receipt created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/GoodsReceipt")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_date' => 'nullable|date',
            'received_by' => 'nullable|string|max:100',
            'delivery_note_number' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'line_items' => 'required|array|min:1',
            'line_items.*.purchase_order_line_item_id' => 'required|exists:purchase_order_line_items,id',
            'line_items.*.received_quantity' => 'required|numeric|min:0',
            'line_items.*.location_id' => 'nullable|exists:warehouse_locations,id',
            'line_items.*.batch_number' => 'nullable|string|max:100',
            'line_items.*.serial_number' => 'nullable|string|max:100',
            'line_items.*.expiry_date' => 'nullable|date',
        ]);

        try {
            $receipt = $this->service->createFromPurchaseOrder(
                $validated['purchase_order_id'],
                $validated
            );

            return $this->created($receipt, 'Goods receipt created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Mark goods receipt as received
     */
    public function markAsReceived(int $id): JsonResponse
    {
        try {
            $receipt = $this->service->markAsReceived($id);

            return $this->updated($receipt, 'Goods receipt marked as received');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Inspect goods receipt
     */
    public function inspect(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'line_items' => 'required|array|min:1',
            'line_items.*.line_item_id' => 'required|exists:goods_receipt_line_items,id',
            'line_items.*.accepted_quantity' => 'required|numeric|min:0',
            'line_items.*.rejected_quantity' => 'required|numeric|min:0',
            'line_items.*.inspection_status' => 'required|in:pending,passed,failed',
            'line_items.*.inspection_notes' => 'nullable|string',
            'line_items.*.rejection_reason' => 'nullable|string',
        ]);

        try {
            $receipt = $this->service->inspect($id, $validated);

            return $this->updated($receipt, 'Goods receipt inspected');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Accept goods receipt
     */
    public function accept(int $id): JsonResponse
    {
        try {
            $receipt = $this->service->accept($id);

            return $this->updated($receipt, 'Goods receipt accepted and inventory updated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Reject goods receipt
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $receipt = $this->service->reject($id, $validated['reason']);

            return $this->updated($receipt, 'Goods receipt rejected');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Get goods receipts for a purchase order
     */
    public function getByPurchaseOrder(int $purchaseOrderId): JsonResponse
    {
        $receipts = $this->service->getByPurchaseOrder($purchaseOrderId);

        return $this->success($receipts);
    }

    /**
     * Delete goods receipt (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->deleted('Goods receipt deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
