<?php

declare(strict_types=1);

namespace Modules\Order\Application\Contracts;

use Modules\Order\Domain\Entities\OrderLine;

interface OrderLineServiceInterface
{
    public function getOrderLine(string $tenantId, string $id): OrderLine;

    /** @return OrderLine[] */
    public function getLinesForOrder(string $tenantId, string $orderType, string $orderId): array;

    public function addOrderLine(string $tenantId, array $data): OrderLine;

    public function updateOrderLine(string $tenantId, string $id, array $data): OrderLine;

    public function deleteOrderLine(string $tenantId, string $id): void;
}
