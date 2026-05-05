<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\PartyManagement\Domain\Entities\AssetOwnership;
use Modules\PartyManagement\Domain\RepositoryInterfaces\AssetOwnershipRepositoryInterface;
use Modules\PartyManagement\Infrastructure\Persistence\Eloquent\Models\AssetOwnershipModel;
use RuntimeException;

class EloquentAssetOwnershipRepository implements AssetOwnershipRepositoryInterface
{
    public function create(AssetOwnership $ownership): AssetOwnership
    {
        AssetOwnershipModel::create([
            'id'               => $ownership->getId(),
            'tenant_id'        => $ownership->getTenantId(),
            'party_id'         => $ownership->getPartyId(),
            'asset_id'         => $ownership->getAssetId(),
            'ownership_type'   => $ownership->getOwnershipType(),
            'acquisition_date' => $ownership->getAcquisitionDate()->format('Y-m-d H:i:s'),
            'disposal_date'    => $ownership->getDisposalDate()?->format('Y-m-d H:i:s'),
            'acquisition_cost' => $ownership->getAcquisitionCost(),
            'notes'            => $ownership->getNotes(),
        ]);

        return $this->findById($ownership->getTenantId(), $ownership->getId());
    }

    public function update(AssetOwnership $ownership): AssetOwnership
    {
        AssetOwnershipModel::where('tenant_id', $ownership->getTenantId())
            ->where('id', $ownership->getId())
            ->update([
                'disposal_date' => $ownership->getDisposalDate()?->format('Y-m-d H:i:s'),
                'notes'         => $ownership->getNotes(),
            ]);

        return $this->findById($ownership->getTenantId(), $ownership->getId());
    }

    public function findById(int $tenantId, string $id): AssetOwnership
    {
        $model = AssetOwnershipModel::where('tenant_id', $tenantId)->where('id', $id)->first();

        if ($model === null) {
            throw new RuntimeException("AssetOwnership not found: {$id}");
        }

        return $this->toDomain($model);
    }

    public function getByParty(int $tenantId, string $partyId): array
    {
        return AssetOwnershipModel::where('tenant_id', $tenantId)
            ->where('party_id', $partyId)
            ->orderBy('acquisition_date', 'desc')
            ->get()
            ->map(fn (AssetOwnershipModel $m) => $this->toDomain($m))
            ->all();
    }

    public function getByAsset(int $tenantId, string $assetId): array
    {
        return AssetOwnershipModel::where('tenant_id', $tenantId)
            ->where('asset_id', $assetId)
            ->orderBy('acquisition_date', 'desc')
            ->get()
            ->map(fn (AssetOwnershipModel $m) => $this->toDomain($m))
            ->all();
    }

    public function getByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return AssetOwnershipModel::where('tenant_id', $tenantId)
            ->orderBy('acquisition_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function toDomain(AssetOwnershipModel $model): AssetOwnership
    {
        return new AssetOwnership(
            id: $model->id,
            tenantId: $model->tenant_id,
            partyId: $model->party_id,
            assetId: $model->asset_id,
            ownershipType: $model->ownership_type,
            acquisitionDate: new DateTimeImmutable($model->acquisition_date),
            disposalDate: $model->disposal_date !== null ? new DateTimeImmutable($model->disposal_date) : null,
            acquisitionCost: (string) $model->acquisition_cost,
            notes: $model->notes,
        );
    }
}
