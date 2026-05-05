<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Product\Application\Contracts\RebuildProductSearchProjectionServiceInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductSearchProjectionRepositoryInterface;

class RebuildProductSearchProjectionService implements RebuildProductSearchProjectionServiceInterface
{
    public function __construct(
        private readonly ProductSearchProjectionRepositoryInterface $projectionRepository,
    ) {}

    public function execute(int $tenantId): int
    {
        return $this->projectionRepository->rebuildForTenant($tenantId);
    }
}
