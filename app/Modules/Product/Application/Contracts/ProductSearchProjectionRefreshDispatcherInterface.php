<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

interface ProductSearchProjectionRefreshDispatcherInterface
{
    public function dispatch(int $tenantId, int $productId, string $debounceKey, int $debounceSeconds): void;
}
