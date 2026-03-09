<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

/**
 * Inventory Service Interface
 */
interface InventoryServiceInterface
{
    public function createProduct(array $data): array;

    public function updateProduct(int|string $id, array $data): array;

    public function deleteProduct(int|string $id): bool;

    public function getProduct(int|string $id): ?array;

    public function listProducts(int|string $tenantId, array $filters = []): array;

    /**
     * Reserve stock for an order (part of Saga workflow).
     * Returns reservation ID on success.
     */
    public function reserveStock(int|string $productId, int $quantity, string $orderId): string;

    /**
     * Release a stock reservation (Saga compensation step).
     */
    public function releaseReservation(string $reservationId): bool;

    /**
     * Confirm stock deduction after order completion.
     */
    public function confirmStockDeduction(string $reservationId): bool;
}
