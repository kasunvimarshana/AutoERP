<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Application\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\PartyManagement\Application\Contracts\ManageAssetOwnershipServiceInterface;
use Modules\PartyManagement\Domain\Entities\AssetOwnership;
use Modules\PartyManagement\Domain\RepositoryInterfaces\AssetOwnershipRepositoryInterface;

class ManageAssetOwnershipService implements ManageAssetOwnershipServiceInterface
{
    public function __construct(
        private readonly AssetOwnershipRepositoryInterface $ownerships,
    ) {}

    public function create(array $data): AssetOwnership
    {
        return DB::transaction(function () use ($data): AssetOwnership {
            $ownership = new AssetOwnership(
                id: Str::uuid()->toString(),
                tenantId: (int) $data['tenant_id'],
                partyId: $data['party_id'],
                assetId: $data['asset_id'],
                ownershipType: $data['ownership_type'],
                acquisitionDate: new DateTimeImmutable($data['acquisition_date']),
                disposalDate: isset($data['disposal_date']) ? new DateTimeImmutable($data['disposal_date']) : null,
                acquisitionCost: (string) $data['acquisition_cost'],
                notes: $data['notes'] ?? null,
            );

            return $this->ownerships->create($ownership);
        });
    }

    public function update(int $tenantId, string $id, array $data): AssetOwnership
    {
        return DB::transaction(function () use ($tenantId, $id, $data): AssetOwnership {
            $ownership = $this->ownerships->findById($tenantId, $id);

            if (array_key_exists('disposal_date', $data) && $data['disposal_date'] !== null) {
                $ownership->dispose(new DateTimeImmutable($data['disposal_date']));
            }

            return $this->ownerships->update($ownership);
        });
    }

    public function find(int $tenantId, string $id): AssetOwnership
    {
        return $this->ownerships->findById($tenantId, $id);
    }

    public function listByParty(int $tenantId, string $partyId): array
    {
        return $this->ownerships->getByParty($tenantId, $partyId);
    }

    public function listByAsset(int $tenantId, string $assetId): array
    {
        return $this->ownerships->getByAsset($tenantId, $assetId);
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->ownerships->getByTenant($tenantId, $perPage, $page);
    }
}
