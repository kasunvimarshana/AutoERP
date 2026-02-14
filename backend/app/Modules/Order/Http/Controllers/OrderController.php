<?php

namespace App\Modules\Order\Http\Controllers;

use App\Base\BaseController;
use App\Modules\Order\Services\OrderService;
use App\Modules\Order\DTOs\OrderDTO;
use App\Modules\Order\DTOs\OrderItemDTO;
use App\Modules\Order\Http\Requests\StoreOrderRequest;
use App\Modules\Order\Http\Requests\UpdateOrderRequest;
use App\Modules\Order\Http\Requests\StoreOrderItemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="Order management endpoints"
 * )
 */
class OrderController extends BaseController
{
    protected OrderService $service;

    public function __construct(OrderService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders",
     *     tags={"Orders"},
     *     summary="List all orders",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Orders retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $orders = $this->service->paginate($perPage, ['tenant', 'branch', 'customer', 'orderItems']);
            
            return $this->successResponse($orders, 'Orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders",
     *     tags={"Orders"},
     *     summary="Create a new order",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_id", "branch_id", "customer_id", "order_number", "order_date"},
     *             @OA\Property(property="tenant_id", type="string", format="uuid"),
     *             @OA\Property(property="branch_id", type="string", format="uuid"),
     *             @OA\Property(property="customer_id", type="string", format="uuid"),
     *             @OA\Property(property="order_number", type="string", example="ORD-2024-001"),
     *             @OA\Property(property="order_date", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "processing", "completed", "cancelled"}),
     *             @OA\Property(property="total_amount", type="number", format="float"),
     *             @OA\Property(property="tax_amount", type="number", format="float"),
     *             @OA\Property(property="discount_amount", type="number", format="float"),
     *             @OA\Property(property="grand_total", type="number", format="float"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="settings", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $dto = OrderDTO::fromArray($request->validated());
            $order = $this->service->createOrder($dto);
            
            return $this->successResponse($order, 'Order created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{id}",
     *     tags={"Orders"},
     *     summary="Get a specific order",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $order = $this->service->findById($id, ['tenant', 'branch', 'customer', 'orderItems.productVariant']);
            
            if (!$order) {
                return $this->errorResponse('Order not found', 404);
            }
            
            return $this->successResponse($order, 'Order retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/orders/{id}",
     *     tags={"Orders"},
     *     summary="Update an order",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="branch_id", type="string", format="uuid"),
     *             @OA\Property(property="customer_id", type="string", format="uuid"),
     *             @OA\Property(property="order_number", type="string"),
     *             @OA\Property(property="order_date", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "processing", "completed", "cancelled"}),
     *             @OA\Property(property="total_amount", type="number", format="float"),
     *             @OA\Property(property="tax_amount", type="number", format="float"),
     *             @OA\Property(property="discount_amount", type="number", format="float"),
     *             @OA\Property(property="grand_total", type="number", format="float"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="settings", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
    public function update(UpdateOrderRequest $request, string $id): JsonResponse
    {
        try {
            $dto = OrderDTO::fromArray($request->validated());
            $result = $this->service->updateOrder($id, $dto);
            
            if (!$result) {
                return $this->errorResponse('Order not found or update failed', 404);
            }
            
            $order = $this->service->findById($id, ['tenant', 'branch', 'customer', 'orderItems']);
            
            return $this->successResponse($order, 'Order updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/orders/{id}",
     *     tags={"Orders"},
     *     summary="Delete an order",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $result = $this->service->deleteOrder($id);
            
            if (!$result) {
                return $this->errorResponse('Order not found or delete failed', 404);
            }
            
            return $this->successResponse(null, 'Order deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{id}/cancel",
     *     tags={"Orders"},
     *     summary="Cancel an order",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order cancelled successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Order cannot be cancelled"
     *     )
     * )
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $result = $this->service->cancelOrder($id);
            
            if (!$result) {
                return $this->errorResponse('Order not found or cancel failed', 404);
            }
            
            $order = $this->service->findById($id, ['tenant', 'branch', 'customer', 'orderItems']);
            
            return $this->successResponse($order, 'Order cancelled successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{id}/complete",
     *     tags={"Orders"},
     *     summary="Complete an order",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order completed successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Order cannot be completed"
     *     )
     * )
     */
    public function complete(string $id): JsonResponse
    {
        try {
            $result = $this->service->completeOrder($id);
            
            if (!$result) {
                return $this->errorResponse('Order not found or complete failed', 404);
            }
            
            $order = $this->service->findById($id, ['tenant', 'branch', 'customer', 'orderItems']);
            
            return $this->successResponse($order, 'Order completed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{id}/items",
     *     tags={"Orders"},
     *     summary="Get all items for an order",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order items retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/OrderItem"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
    public function items(string $id): JsonResponse
    {
        try {
            $order = $this->service->findById($id, ['orderItems.productVariant.product']);
            
            if (!$order) {
                return $this->errorResponse('Order not found', 404);
            }
            
            return $this->successResponse($order->orderItems, 'Order items retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{id}/items",
     *     tags={"Orders"},
     *     summary="Add an item to an order",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_variant_id", "quantity", "unit_price"},
     *             @OA\Property(property="product_variant_id", type="string", format="uuid"),
     *             @OA\Property(property="quantity", type="number", format="float"),
     *             @OA\Property(property="unit_price", type="number", format="float"),
     *             @OA\Property(property="tax_rate", type="number", format="float"),
     *             @OA\Property(property="discount_amount", type="number", format="float"),
     *             @OA\Property(property="line_total", type="number", format="float"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order item added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order item added successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/OrderItem")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */
    public function addItem(StoreOrderItemRequest $request, string $id): JsonResponse
    {
        try {
            $dto = OrderItemDTO::fromArray($request->validated());
            $orderItem = $this->service->addOrderItem($id, $dto);
            
            return $this->successResponse($orderItem, 'Order item added successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
