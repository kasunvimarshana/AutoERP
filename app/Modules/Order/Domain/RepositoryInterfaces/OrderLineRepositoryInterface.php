<?php

declare(strict_types=1);

namespace Modules\Order\Domain\RepositoryInterfaces;

use Modules\Order\Domain\Entities\OrderLine;

interface OrderLineRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?OrderLine;

    /** @return OrderLine[] */
    public function findByOrder(string $tenantId, string $orderType, string $orderId): array;

    public function save(OrderLine $orderLine): void;

    public function delete(string $tenantId, string $id): void;
}
