<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

interface RefreshProductSearchProjectionServiceInterface
{
    public function execute(int $tenantId, int $productId): int;
}
