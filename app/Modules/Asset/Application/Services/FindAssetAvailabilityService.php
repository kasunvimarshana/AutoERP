<?php

declare(strict_types=1);

namespace Modules\Asset\Application\Services;

use Modules\Asset\Application\Contracts\FindAssetAvailabilityServiceInterface;
use Modules\Asset\Domain\Entities\AssetAvailabilityState;
use Modules\Asset\Domain\RepositoryInterfaces\AssetAvailabilityStateRepositoryInterface;

class FindAssetAvailabilityService implements FindAssetAvailabilityServiceInterface
{
    public function __construct(
        private readonly AssetAvailabilityStateRepositoryInterface $availabilityRepository,
    ) {}

    public function findCurrentState(int $tenantId, int $assetId): ?AssetAvailabilityState
    {
        return $this->availabilityRepository->findCurrentState($tenantId, $assetId);
    }
}
