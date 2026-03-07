<?php

namespace App\Modules\Order\Services;

use App\Modules\Order\Repositories\Contracts\OrderRepositoryInterface;
use App\Modules\Order\DTOs\OrderDTO;
use App\Modules\Order\Events\OrderCreated;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    private OrderRepositoryInterface $orderRepository;
    private ProductGatewayService $productGateway;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ProductGatewayService $productGateway
    ) {
        $this->orderRepository = $orderRepository;
        $this->productGateway = $productGateway;
    }

    public function getAllOrders(array $filters): LengthAwarePaginator
    {
        return $this->orderRepository->getAllWithFilters($filters);
    }

    /**
     * Implementing the 'Order Creation' Saga Start.
     * Includes Cross-Service data enrichment (Product details fetch via Gateway)
     * prior to dispatching Distributed Event.
     */
    public function createOrder(array $data, string $userJwtToken)
    {
        $productId = $data['product_id'];

        // 1. Cross-service validation: Ensure product exists before creating an order
        // Throws if missing, implicitly protecting local DB integrity
        $productDetails = $this->productGateway->getProductDetails($productId, $userJwtToken);

        $orderDTO = OrderDTO::fromArray([
            'user_id' => $data['user_id'],
            'product_id' => $productId,
            'quantity' => $data['quantity'],
            'total_price' => $productDetails['price'] * $data['quantity'], // Computed from remote model
            'status' => 'PENDING', // Saga State
        ]);

        DB::beginTransaction();

        try {
            $order = $this->orderRepository->create($orderDTO->toArray());

            // 2. Dispatch Saga Trigger Domain Event (OrderCreated)
            // The Inventory-Service will consume this and attempt a stock reservation.
            event(new OrderCreated($order));

            DB::commit();

            return $order;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Saga Consumer Method: Called by a listener when InventoryService fires InventoryReservationFailed
     * This is the COMPENSATING transaction rolling back local status.
     */
    public function compensateOrderFailure(int $orderId, string $reason): void
    {
        DB::beginTransaction();
        try {
            $order = $this->orderRepository->findById($orderId);
            $order->update(['status' => 'CANCELLED', 'failure_reason' => $reason]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("CRITICAL SAGA FAILURE: Couldn't compensate Order $orderId locally.", ['details' => $e->getMessage()]);
            // Depending on architecture, you may retry this or manual DB intervention alert.
        }
    }

    /**
     * Saga Consumer Method: Called when InventoryService fires InventoryReserved
     */
    public function finalizeOrderSuccess(int $orderId): void
    {
        DB::beginTransaction();
        try {
            $order = $this->orderRepository->findById($orderId);
            $order->update(['status' => 'APPROVED']);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            // A reverse-compensation would theoretically be required here (cancelling the inventory reservation)
        }
    }
}
