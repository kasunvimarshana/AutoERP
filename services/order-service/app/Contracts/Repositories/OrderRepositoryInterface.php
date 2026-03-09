<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Domain\Order\Models\Order;

interface OrderRepositoryInterface
{
    public function findById(string $id): ?Order;
    public function findBySagaId(string $sagaId): ?Order;
    public function create(array $data): Order;
    public function updateStatus(string $id, string $status, ?array $extra = null): Order;
    public function getByTenant(string $tenantId, array $params = []): mixed;
    public function cancel(string $id, string $reason): Order;
}
