<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Events\OrderCancelled;
use Modules\Sales\Events\OrderCompleted;
use Modules\Sales\Events\OrderConfirmed;
use Modules\Sales\Http\Requests\CancelOrderRequest;
use Modules\Sales\Http\Requests\StoreOrderRequest;
use Modules\Sales\Http\Requests\UpdateOrderRequest;
use Modules\Sales\Http\Resources\OrderResource;
use Modules\Sales\Models\Order;
use Modules\Sales\Repositories\OrderRepository;
use Modules\Sales\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderService $orderService
    ) {}

    /**
     * Display a listing of orders.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->only([
            'status',
            'customer_id',
            'organization_id',
            'from_date',
            'to_date',
            'search',
        ]);

        $perPage = $request->get('per_page', 15);
        $orders = $this->orderRepository->filter($filters, $perPage);

        return ApiResponse::paginated(
            $orders->setCollection(
                $orders->getCollection()->map(fn ($order) => new OrderResource($order))
            ),
            'Orders retrieved successfully'
        );
    }

    /**
     * Store a newly created order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['created_by'] = $request->user()->id;

        $items = $data['items'] ?? [];
        unset($data['items']);

        $order = $this->orderService->createOrder($data, $items);
        $order->load(['organization', 'customer', 'items.product', 'quotation']);

        return ApiResponse::created(
            new OrderResource($order),
            'Order created successfully'
        );
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['organization', 'customer', 'items.product', 'quotation', 'invoices']);

        return ApiResponse::success(
            new OrderResource($order),
            'Order retrieved successfully'
        );
    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $data = $request->validated();

        $order = $this->orderService->updateOrder($order->id, $data);
        $order->load(['organization', 'customer', 'items.product', 'quotation']);

        return ApiResponse::success(
            new OrderResource($order),
            'Order updated successfully'
        );
    }

    /**
     * Remove the specified order.
     */
    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);

        $this->orderService->deleteOrder($order->id);

        return ApiResponse::success(
            null,
            'Order deleted successfully'
        );
    }

    /**
     * Confirm the order.
     */
    public function confirm(Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        if (! $order->status->canConfirm()) {
            return ApiResponse::error(
                'Order cannot be confirmed in its current status',
                422
            );
        }

        $order = $this->orderService->confirmOrder($order->id);
        event(new OrderConfirmed($order));

        $order->load(['organization', 'customer', 'items.product']);

        return ApiResponse::success(
            new OrderResource($order),
            'Order confirmed successfully'
        );
    }

    /**
     * Cancel the order.
     */
    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        if (! $order->status->canCancel()) {
            return ApiResponse::error(
                'Order cannot be cancelled in its current status',
                422
            );
        }

        $order = $this->orderService->cancelOrder($order->id, $request->input('reason'));
        event(new OrderCancelled($order));

        $order->load(['organization', 'customer', 'items.product']);

        return ApiResponse::success(
            new OrderResource($order),
            'Order cancelled successfully'
        );
    }

    /**
     * Complete the order.
     */
    public function complete(Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        if (! $order->status->canComplete()) {
            return ApiResponse::error(
                'Order cannot be completed in its current status',
                422
            );
        }

        $order = $this->orderService->completeOrder($order->id);
        event(new OrderCompleted($order));

        $order->load(['organization', 'customer', 'items.product']);

        return ApiResponse::success(
            new OrderResource($order),
            'Order completed successfully'
        );
    }

    /**
     * Create invoice from order.
     */
    public function createInvoice(Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        if (! in_array($order->status, [OrderStatus::CONFIRMED, OrderStatus::PROCESSING])) {
            return ApiResponse::error(
                'Only confirmed or processing orders can be invoiced',
                422
            );
        }

        $result = $this->orderService->createInvoiceFromOrder($order->id);
        $invoice = $result['invoice'];

        return ApiResponse::success(
            [
                'invoice_id' => $invoice->id,
                'invoice_code' => $invoice->invoice_code,
            ],
            'Invoice created successfully'
        );
    }
}
