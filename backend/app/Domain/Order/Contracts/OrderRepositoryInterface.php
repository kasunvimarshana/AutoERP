<?php

declare(strict_types=1);

namespace App\Domain\Order\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Contract for the Order repository.
 */
interface OrderRepositoryInterface
{
    public function all(array $filters = []): mixed;

    public function findOrFail(int|string $id): Model;

    public function find(int|string $id): ?Model;

    public function create(array $attributes): Model;

    public function update(int|string $id, array $attributes): Model;

    public function delete(int|string $id): bool;

    /** Find an order by its human-readable order number. */
    public function findByOrderNumber(string $orderNumber): ?Model;

    /**
     * Return all orders for a specific customer.
     */
    public function findByCustomer(int|string $customerId): Collection;

    /**
     * Return all orders with the given status.
     */
    public function findByStatus(string $status): Collection;

    /**
     * Transition an order to a new status with optional metadata.
     *
     * @throws \App\Exceptions\InvalidOrderStatusTransitionException
     */
    public function updateStatus(int|string $orderId, string $status, array $metadata = []): Model;

    /**
     * Attach line items to an order within a transaction.
     *
     * @param array<array{product_id: int, quantity: int, unit_price: float}> $items
     */
    public function attachItems(int|string $orderId, array $items): Model;

    /**
     * Return a summary of order counts grouped by status.
     *
     * @return Collection<string, int>
     */
    public function statusSummary(): Collection;
}
