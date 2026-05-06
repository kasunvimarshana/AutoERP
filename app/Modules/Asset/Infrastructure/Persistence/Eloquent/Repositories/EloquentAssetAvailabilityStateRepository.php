<?php

declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Asset\Domain\Entities\AssetAvailabilityState;
use Modules\Asset\Domain\RepositoryInterfaces\AssetAvailabilityStateRepositoryInterface;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Models\AssetAvailabilityEventModel;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Models\AssetAvailabilityStateModel;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentAssetAvailabilityStateRepository extends EloquentRepository implements
    AssetAvailabilityStateRepositoryInterface
{
    private readonly AssetAvailabilityStateModel $stateModel;

    private readonly AssetAvailabilityEventModel $eventModel;

    private readonly AssetModel $assetModel;

    public function __construct(
        AssetAvailabilityStateModel $stateModel,
        AssetAvailabilityEventModel $eventModel,
        AssetModel $assetModel,
    ) {
        $this->stateModel = $stateModel;
        $this->eventModel = $eventModel;
        $this->assetModel = $assetModel;

        parent::__construct($stateModel);
        $this->setDomainEntityMapper(
            fn (AssetAvailabilityStateModel $model): AssetAvailabilityState => $this->mapModelToEntity($model)
        );
    }

    public function findCurrentState(int $tenantId, int $assetId): ?AssetAvailabilityState
    {
        /** @var AssetAvailabilityStateModel|null $model */
        $model = $this->stateModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('asset_id', $assetId)
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findAssetUsageProfile(int $tenantId, int $assetId): ?string
    {
        /** @var AssetModel|null $asset */
        $asset = $this->assetModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $assetId)
            ->first(['usage_profile']);

        return $asset?->usage_profile;
    }

    public function saveCurrentState(AssetAvailabilityState $state): AssetAvailabilityState
    {
        /** @var AssetAvailabilityStateModel|null $existing */
        $existing = $this->stateModel->newQuery()
            ->where('tenant_id', $state->getTenantId())
            ->where('asset_id', $state->getAssetId())
            ->first();

        $payload = [
            'tenant_id' => $state->getTenantId(),
            'org_unit_id' => $state->getOrgUnitId(),
            'asset_id' => $state->getAssetId(),
            'availability_status' => $state->getAvailabilityStatus(),
            'reason_code' => $state->getReasonCode(),
            'source_type' => $state->getSourceType(),
            'source_id' => $state->getSourceId(),
            'effective_from' => $state->getEffectiveFrom(),
            'effective_to' => $state->getEffectiveTo(),
            'updated_by' => $state->getUpdatedBy(),
            'metadata' => $state->getMetadata(),
        ];

        if ($existing !== null) {
            $payload['row_version'] = (int) $existing->row_version + 1;
            $existing->update($payload);
            $model = $existing->fresh();
        } else {
            $payload['row_version'] = $state->getRowVersion();
            $model = $this->stateModel->newQuery()->create($payload);
        }

        /** @var AssetAvailabilityStateModel $model */

        return $this->toDomainEntity($model);
    }

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
    ): void {
        $this->eventModel->newQuery()->create([
            'tenant_id' => $tenantId,
            'org_unit_id' => $orgUnitId,
            'row_version' => 1,
            'asset_id' => $assetId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason_code' => $reasonCode,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'changed_by' => $changedBy,
            'changed_at' => new \DateTimeImmutable(),
            'metadata' => $metadata,
        ]);
    }

    private function mapModelToEntity(AssetAvailabilityStateModel $model): AssetAvailabilityState
    {
        return new AssetAvailabilityState(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            assetId: (int) $model->asset_id,
            availabilityStatus: (string) $model->availability_status,
            reasonCode: $model->reason_code,
            sourceType: $model->source_type,
            sourceId: $model->source_id !== null ? (int) $model->source_id : null,
            updatedBy: $model->updated_by !== null ? (int) $model->updated_by : null,
            effectiveFrom: $model->effective_from,
            effectiveTo: $model->effective_to,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) ($model->row_version ?? 1),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
