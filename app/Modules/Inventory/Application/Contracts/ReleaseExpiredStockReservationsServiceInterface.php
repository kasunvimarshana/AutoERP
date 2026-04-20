<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

interface ReleaseExpiredStockReservationsServiceInterface
{
    public function execute(int $tenantId, ?string $expiresBefore = null): int;
}
