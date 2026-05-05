<?php

declare(strict_types=1);

namespace Modules\Asset\Application\Services;

use Modules\Asset\Application\Contracts\SyncAssetAvailabilityServiceInterface;
use Modules\Asset\Domain\Entities\AssetAvailabilityState;
use Modules\Asset\Domain\RepositoryInterfaces\AssetAvailabilityStateRepositoryInterface;
use Modules\Core\Application\Services\BaseService;

class SyncAssetAvailabilityService extends BaseService implements SyncAssetAvailabilityServiceInterface
{
    public function __construct(private readonly AssetAvailabilityStateRepositoryInterface $availabilityRepository)
    {
        parent::__construct($availabilityRepository);
    }

    protected function handle(array $data): AssetAvailabilityState
    {
        $tenantId = (int) $data['tenant_id'];
        $assetId = (int) $data['asset_id'];
        $targetStatus = (string) $data['target_status'];
        $orgUnitId = isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null;

        $this->assertUsageProfileAllowsStatus($tenantId, $assetId, $targetStatus);

        $current = $this->availabilityRepository->findCurrentState($tenantId, $assetId);
        $fromStatus = $current?->getAvailabilityStatus();

        $state = new AssetAvailabilityState(
            tenantId: $tenantId,
            orgUnitId: $orgUnitId,
            assetId: $assetId,
            availabilityStatus: $targetStatus,
            reasonCode: $data['reason_code'] ?? null,
            sourceType: $data['source_type'] ?? null,
            sourceId: isset($data['source_id']) ? (int) $data['source_id'] : null,
            updatedBy: isset($data['changed_by']) ? (int) $data['changed_by'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
            rowVersion: $current !== null ? $current->getRowVersion() + 1 : 1,
        );

        $saved = $this->availabilityRepository->saveCurrentState($state);

        $this->availabilityRepository->appendTransitionEvent(
            tenantId: $tenantId,
            orgUnitId: $orgUnitId,
            assetId: $assetId,
            fromStatus: $fromStatus,
            toStatus: $targetStatus,
            reasonCode: $state->getReasonCode(),
            sourceType: $state->getSourceType(),
            sourceId: $state->getSourceId(),
            changedBy: $state->getUpdatedBy(),
            metadata: $state->getMetadata(),
        );

        return $saved;
    }

    private function assertUsageProfileAllowsStatus(int $tenantId, int $assetId, string $targetStatus): void
    {
        $usageProfile = $this->availabilityRepository->findAssetUsageProfile($tenantId, $assetId);

        if ($usageProfile === null) {
            throw new \InvalidArgumentException('Asset not found for tenant.');
        }

        if (in_array($targetStatus, ['reserved', 'rented'], true)
            && ! in_array($usageProfile, ['rent_only', 'dual_use'], true)) {
            throw new \InvalidArgumentException('Asset usage profile does not allow rental availability states.');
        }

        if ($targetStatus === 'in_service' && ! in_array($usageProfile, ['service_only', 'dual_use'], true)) {
            throw new \InvalidArgumentException('Asset usage profile does not allow service downtime state.');
        }

        if ($targetStatus === 'internal_use' && $usageProfile === 'service_only') {
            throw new \InvalidArgumentException('Service-only assets cannot be assigned to internal-use availability state.');
        }
    }
}
