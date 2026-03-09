<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Contracts\InventoryServiceInterface;
use App\Domain\Contracts\ProductRepositoryInterface;
use App\Domain\Entities\StockMovement;
use App\Infrastructure\Messaging\MessageBrokerFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Inventory Service Implementation
 *
 * Handles inventory business logic with message broker integration
 * for saga coordination.
 */
class InventoryService implements InventoryServiceInterface
{
    public function __construct(
        protected readonly ProductRepositoryInterface $productRepository,
        protected readonly MessageBrokerFactory $brokerFactory
    ) {}

    public function createProduct(array $data): array
    {
        $product = $this->productRepository->create($data);

        $this->brokerFactory->getBroker()->publish('inventory.product.created', [
            'product_id' => $product->id,
            'tenant_id'  => $product->tenant_id,
            'sku'        => $product->sku,
            'timestamp'  => now()->toIso8601String(),
        ]);

        return $product->toArray();
    }

    public function updateProduct(int|string $id, array $data): array
    {
        $product = $this->productRepository->update($id, $data);

        $this->brokerFactory->getBroker()->publish('inventory.product.updated', [
            'product_id' => $product->id,
            'tenant_id'  => $product->tenant_id,
            'timestamp'  => now()->toIso8601String(),
        ]);

        return $product->toArray();
    }

    public function deleteProduct(int|string $id): bool
    {
        return $this->productRepository->delete($id);
    }

    public function getProduct(int|string $id): ?array
    {
        $product = $this->productRepository->findById($id, ['category', 'stockMovements']);
        return $product?->toArray();
    }

    public function listProducts(int|string $tenantId, array $filters = []): array
    {
        $result = $this->productRepository->findByTenant($tenantId, $filters);

        if ($result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            return [
                'data'      => $result->items(),
                'meta'      => [
                    'current_page' => $result->currentPage(),
                    'last_page'    => $result->lastPage(),
                    'per_page'     => $result->perPage(),
                    'total'        => $result->total(),
                ],
                'paginated' => true,
            ];
        }

        return ['data' => $result->toArray(), 'paginated' => false];
    }

    public function reserveStock(int|string $productId, int $quantity, string $orderId): string
    {
        $reservationId = Str::uuid()->toString();

        $product = $this->productRepository->adjustStock($productId, -$quantity);

        StockMovement::create([
            'tenant_id'      => $product->tenant_id,
            'product_id'     => $productId,
            'type'           => 'out',
            'quantity'       => $quantity,
            'reference'      => $orderId,
            'reference_type' => 'order_reservation',
            'notes'          => "Reserved for order {$orderId}",
        ]);

        Cache::put("reservation:{$reservationId}", [
            'product_id' => $productId,
            'quantity'   => $quantity,
            'order_id'   => $orderId,
            'tenant_id'  => $product->tenant_id,
        ], now()->addHours(24));

        $this->brokerFactory->getBroker()->publish('inventory.stock.reserved', [
            'reservation_id' => $reservationId,
            'product_id'     => $productId,
            'quantity'       => $quantity,
            'order_id'       => $orderId,
            'timestamp'      => now()->toIso8601String(),
        ]);

        return $reservationId;
    }

    public function releaseReservation(string $reservationId): bool
    {
        $reservation = Cache::get("reservation:{$reservationId}");

        if (!$reservation) {
            return false;
        }

        $product = $this->productRepository->adjustStock(
            $reservation['product_id'],
            $reservation['quantity']  // Add back
        );

        StockMovement::create([
            'tenant_id'      => $reservation['tenant_id'],
            'product_id'     => $reservation['product_id'],
            'type'           => 'in',
            'quantity'       => $reservation['quantity'],
            'reference'      => $reservation['order_id'],
            'reference_type' => 'order_cancellation',
            'notes'          => "Released reservation {$reservationId}",
        ]);

        Cache::forget("reservation:{$reservationId}");

        $this->brokerFactory->getBroker()->publish('inventory.stock.released', [
            'reservation_id' => $reservationId,
            'product_id'     => $reservation['product_id'],
            'quantity'       => $reservation['quantity'],
            'timestamp'      => now()->toIso8601String(),
        ]);

        return true;
    }

    public function confirmStockDeduction(string $reservationId): bool
    {
        $reservation = Cache::get("reservation:{$reservationId}");

        if (!$reservation) {
            return false;
        }

        Cache::forget("reservation:{$reservationId}");

        $this->brokerFactory->getBroker()->publish('inventory.stock.deducted', [
            'reservation_id' => $reservationId,
            'product_id'     => $reservation['product_id'],
            'order_id'       => $reservation['order_id'],
            'timestamp'      => now()->toIso8601String(),
        ]);

        return true;
    }
}
