<?php

declare(strict_types=1);

namespace Modules\Order\Application\Contracts;

use Modules\Order\Application\DTOs\OrderData;

interface OrderServiceInterface
{
    public function create(OrderData $dto, int $tenantId): mixed;

    public function update(int $id, OrderData $dto): mixed;

    public function updateStatus(int $id, string $status, int $tenantId): mixed;

    public function delete(int $id): bool;

    public function find(mixed $id): mixed;

    public function list(array $filters = [], ?int $perPage = null): mixed;
}
