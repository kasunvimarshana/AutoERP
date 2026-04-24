<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Product\Application\Contracts\ProductSearchProjectionRefreshDispatcherInterface;
use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;

class RefreshProductSearchProjectionService implements RefreshProductSearchProjectionServiceInterface
{
    private const DEFAULT_DEBOUNCE_SECONDS = 2;

    public function __construct(
        private readonly ProductSearchProjectionRefreshDispatcherInterface $dispatcher,
    ) {}

    public function execute(int $tenantId, int $productId): int
    {
        if ($tenantId <= 0 || $productId <= 0) {
            return 0;
        }

        $debounceSeconds = (int) (config('product.search_projection.debounce_seconds') ?? self::DEFAULT_DEBOUNCE_SECONDS);
        if ($debounceSeconds < 0) {
            $debounceSeconds = self::DEFAULT_DEBOUNCE_SECONDS;
        }

        $debounceKey = sprintf('product-search-projection:refresh:%d:%d', $tenantId, $productId);
        $canSchedule = Cache::add($debounceKey, true, now()->addSeconds(max(1, $debounceSeconds)));

        if (! $canSchedule) {
            return 0;
        }

        $this->dispatcher->dispatch($tenantId, $productId, $debounceKey, $debounceSeconds);

        return 1;
    }
}
