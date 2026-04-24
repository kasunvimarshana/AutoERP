<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Modules\Product\Domain\RepositoryInterfaces\ProductSearchProjectionRepositoryInterface;

class RefreshProductSearchProjectionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly string $debounceKey,
    ) {}

    public function handle(ProductSearchProjectionRepositoryInterface $projectionRepository): void
    {
        try {
            $projectionRepository->rebuildForProduct($this->tenantId, $this->productId);
        } finally {
            Cache::forget($this->debounceKey);
        }
    }
}
