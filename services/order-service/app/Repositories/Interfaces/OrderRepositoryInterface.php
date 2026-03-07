<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    /**
     * Return a paginated, filtered list of orders.
     *
     * @param  array<string, mixed> $filters
     */
    public function getAll(array $filters = []): LengthAwarePaginator;

    /**
     * Return paginated orders for a given customer (Keycloak sub).
     *
     * @param  array<string, mixed> $filters
     */
    public function getByCustomerId(string $customerId, array $filters = []): LengthAwarePaginator;

    /**
     * Find an order by its primary key, optionally with items loaded.
     */
    public function findById(int $id, bool $withItems = false): ?Order;

    /**
     * Find an order by its human-readable order number.
     */
    public function findByOrderNumber(string $orderNumber): ?Order;

    /**
     * Create a new order.
     *
     * @param  array<string, mixed> $data
     */
    public function create(array $data): Order;

    /**
     * Update an existing order.
     *
     * @param  array<string, mixed> $data
     */
    public function update(int $id, array $data): ?Order;

    /**
     * Soft-delete an order.
     */
    public function delete(int $id): bool;

    /**
     * Pessimistic lock on the order row for ACID status transitions.
     */
    public function lockForUpdate(int $id): ?Order;
}
