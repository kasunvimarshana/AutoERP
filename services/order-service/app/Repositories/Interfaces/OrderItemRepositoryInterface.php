<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Collection;

interface OrderItemRepositoryInterface
{
    /**
     * Return all items belonging to an order.
     *
     * @return Collection<int, OrderItem>
     */
    public function getByOrderId(int $orderId): Collection;

    /**
     * Find an order item by its primary key.
     */
    public function findById(int $id): ?OrderItem;

    /**
     * Create a new order item.
     *
     * @param  array<string, mixed> $data
     */
    public function create(array $data): OrderItem;

    /**
     * Bulk-insert order items for a given order.
     *
     * @param  array<int, array<string, mixed>> $items
     * @return Collection<int, OrderItem>
     */
    public function createMany(int $orderId, array $items): Collection;

    /**
     * Update an existing order item.
     *
     * @param  array<string, mixed> $data
     */
    public function update(int $id, array $data): ?OrderItem;

    /**
     * Delete all items for a given order.
     */
    public function deleteByOrderId(int $orderId): int;
}
