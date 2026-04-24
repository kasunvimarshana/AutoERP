<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductSearchProjectionRepositoryInterface;

class RefreshProductSearchProjectionService implements RefreshProductSearchProjectionServiceInterface
{
    public function __construct(
        private readonly ProductSearchProjectionRepositoryInterface $projectionRepository,
    ) {}

    public function execute(int $tenantId, int $productId): int
    {
        return $this->projectionRepository->rebuildForProduct($tenantId, $productId);
    }
}
