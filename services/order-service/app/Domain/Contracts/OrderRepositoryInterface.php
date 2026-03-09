<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Entities\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Order Repository Interface
 */
interface OrderRepositoryInterface
{
    public function findById(int|string $id, array $relations = []): ?Order;

    public function findByOrderNumber(string $orderNumber): ?Order;

    public function findByTenant(int|string $tenantId, array $filters = []): Collection|LengthAwarePaginator;

    public function findBySagaId(string $sagaId): ?Order;

    public function create(array $data): Order;

    public function update(int|string $id, array $data): Order;

    public function updateStatus(int|string $id, string $status): Order;

    public function delete(int|string $id): bool;
}
