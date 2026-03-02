<?php

declare(strict_types=1);

namespace Modules\Sales\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Sales\Application\DTOs\CreateSalesOrderDTO;
use Modules\Sales\Application\Services\SalesService;

/**
 * Sales controller.
 *
 * Input validation and response formatting ONLY.
 * All business logic is delegated to SalesService.
 *
 * @OA\Tag(name="Sales", description="Sales order management endpoints")
 */
class SalesController extends Controller
{
    public function __construct(private readonly SalesService $service) {}

    /**
     * @OA\Post(
     *     path="/api/v1/sales/orders",
     *     tags={"Sales"},
     *     summary="Create a new sales order",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customer_id","order_date","currency_code","lines"},
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="order_date", type="string", format="date"),
     *             @OA\Property(property="currency_code", type="string", example="USD"),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="warehouse_id", type="integer", nullable=true),
     *             @OA\Property(
     *                 property="lines",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"product_id","uom_id","quantity","unit_price","discount_amount","tax_rate"},
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="uom_id", type="integer"),
     *                     @OA\Property(property="quantity", type="string", example="10.0000"),
     *                     @OA\Property(property="unit_price", type="string", example="25.0000"),
     *                     @OA\Property(property="discount_amount", type="string", example="0.0000"),
     *                     @OA\Property(property="tax_rate", type="string", example="0.1000")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Sales order created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id'               => ['required', 'integer'],
            'order_date'                => ['required', 'date'],
            'currency_code'             => ['required', 'string', 'max:10'],
            'notes'                     => ['nullable', 'string'],
            'warehouse_id'              => ['nullable', 'integer'],
            'lines'                     => ['required', 'array', 'min:1'],
            'lines.*.product_id'        => ['required', 'integer'],
            'lines.*.uom_id'            => ['required', 'integer'],
            'lines.*.quantity'          => ['required', 'numeric', 'min:0'],
            'lines.*.unit_price'        => ['required', 'numeric', 'min:0'],
            'lines.*.discount_amount'   => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate'          => ['required', 'numeric', 'min:0'],
        ]);

        $dto   = CreateSalesOrderDTO::fromArray($validated);
        $order = $this->service->createOrder($dto);

        return ApiResponse::created($order->load('lines'), 'Sales order created.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/sales/orders/{id}/confirm",
     *     tags={"Sales"},
     *     summary="Confirm a sales order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sales order confirmed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function confirmOrder(int $id): JsonResponse
    {
        $order = $this->service->confirmOrder($id);

        return ApiResponse::success($order, 'Sales order confirmed.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/sales/orders",
     *     tags={"Sales"},
     *     summary="List sales orders",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of sales orders"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listOrders(Request $request): JsonResponse
    {
        $orders = $this->service->listOrders($request->query());

        return ApiResponse::success($orders, 'Sales orders retrieved.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/sales/orders/{id}",
     *     tags={"Sales"},
     *     summary="Show a sales order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sales order detail"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showOrder(int $id): JsonResponse
    {
        $order = $this->service->showOrder($id);

        return ApiResponse::success($order->load(['lines', 'deliveries', 'invoices']), 'Sales order retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/sales/orders/{id}/cancel",
     *     tags={"Sales"},
     *     summary="Cancel a sales order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sales order cancelled"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function cancelOrder(int $id): JsonResponse
    {
        $order = $this->service->cancelOrder($id);

        return ApiResponse::success($order, 'Sales order cancelled.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/sales/customers",
     *     tags={"Sales"},
     *     summary="List customers",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of customers"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listCustomers(): JsonResponse
    {
        $customers = $this->service->listCustomers();

        return ApiResponse::success($customers, 'Customers retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/sales/orders/{id}/deliveries",
     *     tags={"Sales"},
     *     summary="Create a delivery for a sales order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="shipped_at", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="delivered_at", type="string", format="date-time", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Delivery created"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function createDelivery(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipped_at'   => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
        ]);

        $delivery = $this->service->createDelivery($id, $validated);

        return ApiResponse::created($delivery, 'Delivery created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/sales/orders/{id}/deliveries",
     *     tags={"Sales"},
     *     summary="List deliveries for a sales order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of deliveries"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function listDeliveries(int $id): JsonResponse
    {
        $deliveries = $this->service->listDeliveries($id);

        return ApiResponse::success($deliveries, 'Deliveries retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/sales/orders/{id}/invoices",
     *     tags={"Sales"},
     *     summary="Create an invoice for a sales order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="total_amount", type="string", example="250.0000", nullable=true),
     *             @OA\Property(property="issued_at", type="string", format="date", nullable=true),
     *             @OA\Property(property="due_date", type="string", format="date", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Invoice created"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function createInvoice(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'issued_at'    => ['nullable', 'date'],
            'due_date'     => ['nullable', 'date'],
        ]);

        $invoice = $this->service->createInvoice($id, $validated);

        return ApiResponse::created($invoice, 'Invoice created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/sales/orders/{id}/invoices",
     *     tags={"Sales"},
     *     summary="List invoices for a sales order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of invoices"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function listInvoices(int $id): JsonResponse
    {
        $invoices = $this->service->listInvoices($id);

        return ApiResponse::success($invoices, 'Invoices retrieved.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/sales/invoices/{id}",
     *     tags={"Sales"},
     *     summary="Show a single invoice",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Invoice detail"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showInvoice(int $id): JsonResponse
    {
        $invoice = $this->service->showInvoice($id);

        return ApiResponse::success($invoice, 'Invoice retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/sales/orders/{id}/returns",
     *     tags={"Sales"},
     *     summary="Process a sales return — restores inventory quantities batch/lot-accurately",
     *     description="Each line restores the returned quantity to the matching stock item via a 'return' transaction. Runs in a single DB transaction so all lines succeed or all roll back. Requires InventoryService to be wired via DI for stock restoration.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lines"},
     *             @OA\Property(
     *                 property="lines",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"product_id","warehouse_id","uom_id","quantity","unit_cost"},
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="warehouse_id", type="integer"),
     *                     @OA\Property(property="uom_id", type="integer"),
     *                     @OA\Property(property="quantity", type="string", example="2.0000"),
     *                     @OA\Property(property="unit_cost", type="string", example="5.5000"),
     *                     @OA\Property(property="batch_number", type="string", nullable=true),
     *                     @OA\Property(property="lot_number", type="string", nullable=true),
     *                     @OA\Property(property="notes", type="string", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Return processed; stock restored"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Order not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function createReturn(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'lines'                    => ['required', 'array', 'min:1'],
            'lines.*.product_id'       => ['required', 'integer'],
            'lines.*.warehouse_id'     => ['required', 'integer'],
            'lines.*.uom_id'           => ['required', 'integer'],
            'lines.*.quantity'         => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost'        => ['required', 'numeric', 'min:0'],
            'lines.*.batch_number'     => ['nullable', 'string', 'max:255'],
            'lines.*.lot_number'       => ['nullable', 'string', 'max:255'],
            'lines.*.notes'            => ['nullable', 'string'],
        ]);

        $results = $this->service->createReturn($id, $validated['lines']);

        return ApiResponse::success($results, 'Sales return processed; stock restored.');
    }
}
