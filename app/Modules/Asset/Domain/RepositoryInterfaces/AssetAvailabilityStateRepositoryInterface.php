<?php

declare(strict_types=1);

namespace Modules\Asset\Domain\RepositoryInterfaces;

use Modules\Asset\Domain\Entities\AssetAvailabilityState;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface AssetAvailabilityStateRepositoryInterface extends RepositoryInterface
{
    public function findCurrentState(int $tenantId, int $assetId): ?AssetAvailabilityState;

    public function findAssetUsageProfile(int $tenantId, int $assetId): ?string;

    public function saveCurrentState(AssetAvailabilityState $state): AssetAvailabilityState;

    public function appendTransitionEvent(
        int $tenantId,
        ?int $orgUnitId,
        int $assetId,
        ?string $fromStatus,
        string $toStatus,
        ?string $reasonCode,
        ?string $sourceType,
        ?int $sourceId,
        ?int $changedBy,
        ?array $metadata = null,
    ): void;
}
