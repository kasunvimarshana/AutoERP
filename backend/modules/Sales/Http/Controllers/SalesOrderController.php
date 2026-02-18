<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Sales\Http\Resources\SalesOrderResource;
use Modules\Sales\Services\SalesOrderService;

/**
 * Sales Order Controller
 *
 * Handles HTTP requests for sales order management including creation,
 * retrieval, updating, and deletion of sales orders with line items.
 */
class SalesOrderController extends BaseController
{
    /**
     * Constructor
     */
    public function __construct(
        private SalesOrderService $salesOrderService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/sales/orders",
     *     summary="List all sales orders",
     *     description="Retrieve paginated list of all sales orders with filtering, sorting, and search capabilities",
     *     operationId="salesOrdersIndex",
     *     tags={"Sales-Orders"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by order status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"draft", "pending", "confirmed", "processing", "shipped", "delivered", "cancelled", "completed"}, example="confirmed")
     *     ),
     *     @OA\Parameter(
     *         name="customer_id",
     *         in="query",
     *         description="Filter by customer ID",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter orders from this date (inclusive)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter orders to this date (inclusive)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sales orders retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/SalesOrder")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string", example="http://api.example.com/api/sales/orders?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://api.example.com/api/sales/orders?page=10"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", example="http://api.example.com/api/sales/orders?page=2")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="to", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=150)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->input('status'),
                'customer_id' => $request->input('customer_id'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'per_page' => $request->integer('per_page', 15),
            ];

            $orders = $this->salesOrderService->getAll($filters);

            return $this->success(SalesOrderResource::collection($orders));
        } catch (\Exception $e) {
            return $this->error('Failed to fetch sales orders: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sales/orders",
     *     summary="Create a new sales order",
     *     description="Create a new sales order with line items. The order will be created in draft status by default.",
     *     operationId="salesOrdersStore",
     *     tags={"Sales-Orders"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Sales order data",
     *         @OA\JsonContent(ref="#/components/schemas/StoreSalesOrderRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Sales order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sales order created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/SalesOrder")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid data",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|uuid|exists:customers,id',
                'order_date' => 'required|date',
                'delivery_date' => 'nullable|date',
                'billing_address' => 'nullable|string',
                'shipping_address' => 'nullable|string',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|uuid|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
                'items.*.discount_amount' => 'nullable|numeric|min:0',
            ]);

            $order = $this->salesOrderService->create($validated);

            return $this->created(SalesOrderResource::make($order), 'Sales order created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to create sales order: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/sales/orders/{id}",
     *     summary="Get sales order details",
     *     description="Retrieve detailed information about a specific sales order including all line items and customer details",
     *     operationId="salesOrdersShow",
     *     tags={"Sales-Orders"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Sales Order ID (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sales order details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/SalesOrder")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sales order not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $order = $this->salesOrderService->getById($id);

            return $this->success(SalesOrderResource::make($order));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Sales order not found');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch sales order: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/sales/orders/{id}",
     *     summary="Update sales order",
     *     description="Update an existing sales order. Can update order details, status, and line items.",
     *     operationId="salesOrdersUpdate",
     *     tags={"Sales-Orders"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Sales Order ID (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Sales order data to update",
     *         @OA\JsonContent(ref="#/components/schemas/UpdateSalesOrderRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sales order updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sales order updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/SalesOrder")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid data",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sales order not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'sometimes|uuid|exists:customers,id',
                'status' => 'sometimes|string|in:draft,pending,confirmed,processing,shipped,delivered,cancelled,completed',
                'order_date' => 'sometimes|date',
                'delivery_date' => 'nullable|date',
                'billing_address' => 'nullable|string',
                'shipping_address' => 'nullable|string',
                'notes' => 'nullable|string',
                'items' => 'sometimes|array|min:1',
            ]);

            $order = $this->salesOrderService->update($id, $validated);

            return $this->updated($order, 'Sales order updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Sales order not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update sales order: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/sales/orders/{id}",
     *     summary="Delete sales order",
     *     description="Soft delete a sales order. The order will be marked as deleted but retained in the database for audit purposes.",
     *     operationId="salesOrdersDestroy",
     *     tags={"Sales-Orders"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Sales Order ID (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440010")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Sales order deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sales order deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sales order not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->salesOrderService->delete($id);

            return $this->deleted('Sales order deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Sales order not found');
        } catch (\Exception $e) {
            return $this->error('Failed to delete sales order: '.$e->getMessage(), 500);
        }
    }
}
