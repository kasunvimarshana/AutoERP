<?php

declare(strict_types=1);

namespace Modules\Purchasing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Purchasing\Http\Requests\StorePurchaseOrderRequest;
use Modules\Purchasing\Http\Requests\UpdatePurchaseOrderRequest;
use Modules\Purchasing\Http\Resources\PurchaseOrderResource;
use Modules\Purchasing\Services\PurchaseOrderService;

/**
 * Purchase Order Controller
 *
 * Handles HTTP requests for purchase order operations.
 */
class PurchaseOrderController extends BaseController
{
    public function __construct(
        protected PurchaseOrderService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/api/purchasing/purchase-orders",
     *     summary="List purchase orders",
     *     description="Retrieve paginated list of purchase orders with optional filtering",
     *     operationId="purchaseOrdersIndex",
     *     tags={"Purchasing-Orders"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="supplier_id",
     *         in="query",
     *         description="Filter by supplier ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"draft", "submitted", "approved", "received", "cancelled"}, example="draft")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter by start date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2026-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter by end date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2026-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in order number and notes",
     *         required=false,
     *         @OA\Schema(type="string", example="PO-2026")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Purchase orders retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/PurchaseOrder")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'supplier_id', 'status', 'from_date', 'to_date',
            'search', 'sort_by', 'sort_order', 'per_page',
        ]);

        $orders = $this->service->list($filters);

        return $this->successResponse(PurchaseOrderResource::collection($orders), 'Purchase orders retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/purchasing/purchase-orders/{id}",
     *     summary="Get purchase order details",
     *     description="Retrieve detailed information about a specific purchase order",
     *     operationId="purchaseOrderShow",
     *     tags={"Purchasing-Orders"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Purchase order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Purchase order retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseOrder")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Purchase order not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->service->find($id);

        if (! $order) {
            return $this->errorResponse('Purchase order not found', 404);
        }

        return $this->successResponse(PurchaseOrderResource::make($order), 'Purchase order retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/purchasing/purchase-orders",
     *     summary="Create purchase order",
     *     description="Create a new purchase order",
     *     operationId="purchaseOrderStore",
     *     tags={"Purchasing-Orders"},
     *     security={{"sanctum_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Purchase order data",
     *         @OA\JsonContent(ref="#/components/schemas/PurchaseOrderRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Purchase order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Purchase order created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseOrder")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->service->create($request->validated());

            return $this->successResponse(PurchaseOrderResource::make($order), 'Purchase order created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/purchasing/purchase-orders/{id}",
     *     summary="Update purchase order",
     *     description="Update an existing purchase order",
     *     operationId="purchaseOrderUpdate",
     *     tags={"Purchasing-Orders"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Purchase order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated purchase order data",
     *         @OA\JsonContent(ref="#/components/schemas/PurchaseOrderRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Purchase order updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Purchase order updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseOrder")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Purchase order not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function update(UpdatePurchaseOrderRequest $request, int $id): JsonResponse
    {
        try {
            $order = $this->service->update($id, $request->validated());

            return $this->successResponse(PurchaseOrderResource::make($order), 'Purchase order updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/purchasing/purchase-orders/{id}",
     *     summary="Delete purchase order",
     *     description="Delete a purchase order",
     *     operationId="purchaseOrderDestroy",
     *     tags={"Purchasing-Orders"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Purchase order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Purchase order deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Purchase order deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Purchase order not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->successResponse(null, 'Purchase order deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/purchasing/purchase-orders/{id}/approve",
     *     summary="Approve purchase order",
     *     description="Approve a purchase order for processing",
     *     operationId="purchaseOrderApprove",
     *     tags={"Purchasing-Orders"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Purchase order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Purchase order approved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Purchase order approved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseOrder")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Purchase order not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $order = $this->service->approve($id);

            return $this->successResponse($order, 'Purchase order approved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/purchasing/purchase-orders/{id}/submit",
     *     summary="Submit purchase order",
     *     description="Submit purchase order to supplier",
     *     operationId="purchaseOrderSubmit",
     *     tags={"Purchasing-Orders"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Purchase order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Purchase order submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Purchase order submitted successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseOrder")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Purchase order not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function submit(int $id): JsonResponse
    {
        try {
            $order = $this->service->submit($id);

            return $this->successResponse($order, 'Purchase order submitted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/purchasing/purchase-orders/{id}/cancel",
     *     summary="Cancel purchase order",
     *     description="Cancel a purchase order",
     *     operationId="purchaseOrderCancel",
     *     tags={"Purchasing-Orders"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Purchase order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Purchase order cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Purchase order cancelled successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseOrder")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Purchase order not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $order = $this->service->cancel($id);

            return $this->successResponse($order, 'Purchase order cancelled successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
