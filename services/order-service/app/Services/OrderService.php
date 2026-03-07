<?php

namespace App\Services;

use App\Events\OrderCancelled;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Saga\SagaOrchestrator;
use App\Saga\Steps\CreateOrderStep;
use App\Saga\Steps\ProcessPaymentStep;
use App\Saga\Steps\ReserveInventoryStep;
use App\Saga\Steps\SendConfirmationStep;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderService extends BaseService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SagaOrchestrator         $sagaOrchestrator,
    ) {}

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    /**
     * Create a new order by running the Saga with all 4 steps.
     *
     * @param  array<string, mixed>  $data
     */
    public function createOrder(array $data): Order
    {
        $tenantId = $this->currentTenantId();

        // Build a clean saga payload
        $payload = array_merge($data, [
            'tenant_id'    => $tenantId,
            'order_number' => Order::generateOrderNumber($tenantId),
        ]);

        // Register saga steps in execution order
        $this->sagaOrchestrator
            ->reset()
            ->addStep(app(ReserveInventoryStep::class))
            ->addStep(app(CreateOrderStep::class))
            ->addStep(app(ProcessPaymentStep::class))
            ->addStep(app(SendConfirmationStep::class));

        $result = $this->sagaOrchestrator->execute($payload);

        if (! $result['success']) {
            throw new \RuntimeException(
                'Order creation failed: ' . ($result['error'] ?? 'Unknown error')
            );
        }

        /** @var Order $order */
        $order = $result['context']['order'] ?? null;

        if (! $order) {
            throw new \RuntimeException('Order creation failed – saga did not produce an order.');
        }

        return $order->load('items');
    }

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    public function getOrders(Request $request): Collection|LengthAwarePaginator
    {
        $tenantId = $this->currentTenantId();

        return $this->orderRepository->getByTenant($tenantId, $request);
    }

    public function getOrdersByStatus(string $status): Collection
    {
        return $this->orderRepository->getByStatus($this->currentTenantId(), $status);
    }

    public function getOrdersByCustomer(string $customerId, Request $request): Collection|LengthAwarePaginator
    {
        return $this->orderRepository->getByCustomer($this->currentTenantId(), $customerId, $request);
    }

    /**
     * Retrieve a single order and enrich order items with live product data.
     */
    public function getOrderWithDetails(string $id): ?array
    {
        $tenantId = $this->currentTenantId();

        /** @var Order|null $order */
        $order = $this->orderRepository->find($id);

        if (! $order || $order->tenant_id !== $tenantId) {
            return null;
        }

        $orderArray = $order->load('items')->toArray();

        // Enrich with product data from Product Service
        $orderArray['items'] = array_map(function (array $item) {
            $productData = $this->fetchProductDetails($item['product_id']);
            if ($productData) {
                $item['product_details'] = $productData;
            }

            return $item;
        }, $orderArray['items'] ?? []);

        return $orderArray;
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function updateOrder(string $id, array $data): ?Order
    {
        $tenantId = $this->currentTenantId();
        $order    = $this->orderRepository->find($id);

        if (! $order || $order->tenant_id !== $tenantId) {
            return null;
        }

        return $this->orderRepository->update($id, $data);
    }

    public function updateOrderStatus(string $id, string $newStatus): Order
    {
        $tenantId = $this->currentTenantId();

        /** @var Order|null $order */
        $order = $this->orderRepository->find($id);

        if (! $order || $order->tenant_id !== $tenantId) {
            throw new \RuntimeException('Order not found.');
        }

        $oldStatus = $order->status;

        /** @var Order $updated */
        $updated = $this->orderRepository->update($id, ['status' => $newStatus]);

        event(new OrderStatusChanged($order->id, $oldStatus, $newStatus));

        Log::info('OrderService: status updated', [
            'order_id'   => $id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        return $updated;
    }

    // -------------------------------------------------------------------------
    // Cancel
    // -------------------------------------------------------------------------

    /**
     * Cancel an order and release any reserved inventory.
     */
    public function cancelOrder(string $id): void
    {
        $tenantId = $this->currentTenantId();

        /** @var Order|null $order */
        $order = $this->orderRepository->find($id);

        if (! $order || $order->tenant_id !== $tenantId) {
            throw new \RuntimeException('Order not found.');
        }

        if (! $order->isCancellable()) {
            throw new \RuntimeException(
                "Order cannot be cancelled from status '{$order->status}'."
            );
        }

        $this->orderRepository->update($id, [
            'status'         => Order::STATUS_CANCELLED,
            'payment_status' => 'refunded',
        ]);

        // Release inventory reservations
        $this->releaseInventory($order);

        event(new OrderCancelled($order->id, 'Cancelled by user'));

        Log::info('OrderService: order cancelled', ['order_id' => $id]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function fetchProductDetails(string $productId): ?array
    {
        $baseUrl = config('services.product_service.url', 'http://product-service');

        try {
            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'X-Service-Key' => config('services.internal_key', ''),
            ])
                ->timeout(5)
                ->get("{$baseUrl}/api/products/{$productId}");

            return $response->successful() ? $response->json('data') : null;
        } catch (\Throwable $e) {
            Log::warning('OrderService: failed to fetch product details', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function releaseInventory(Order $order): void
    {
        $baseUrl = config('services.inventory_service.url', 'http://inventory-service');

        $items = $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity'   => $item->quantity,
        ])->toArray();

        try {
            Http::withHeaders([
                'Accept'        => 'application/json',
                'X-Service-Key' => config('services.internal_key', ''),
            ])
                ->timeout(10)
                ->post("{$baseUrl}/api/inventory/release", [
                    'tenant_id' => $order->tenant_id,
                    'order_id'  => $order->id,
                    'items'     => $items,
                ]);
        } catch (\Throwable $e) {
            Log::error('OrderService: failed to release inventory', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
