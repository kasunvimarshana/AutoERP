<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Jobs;

use Modules\Product\Application\Contracts\ProductSearchProjectionRefreshDispatcherInterface;

class QueuedProductSearchProjectionRefreshDispatcher implements ProductSearchProjectionRefreshDispatcherInterface
{
    public function dispatch(int $tenantId, int $productId, string $debounceKey, int $debounceSeconds): void
    {
        RefreshProductSearchProjectionJob::dispatch($tenantId, $productId, $debounceKey)
            ->delay(now()->addSeconds($debounceSeconds));
    }
}