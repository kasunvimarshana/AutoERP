<?php

declare(strict_types=1);

namespace Modules\Asset\Application\Contracts;

use Modules\Asset\Domain\Entities\AssetAvailabilityState;

interface FindAssetAvailabilityServiceInterface
{
    public function findCurrentState(int $tenantId, int $assetId): ?AssetAvailabilityState;
}
