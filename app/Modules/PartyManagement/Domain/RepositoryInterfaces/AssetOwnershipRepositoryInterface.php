<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Domain\RepositoryInterfaces;

use Modules\PartyManagement\Domain\Entities\AssetOwnership;

interface AssetOwnershipRepositoryInterface
{
    public function create(AssetOwnership $ownership): AssetOwnership;

    public function update(AssetOwnership $ownership): AssetOwnership;

    public function findById(int $tenantId, string $id): AssetOwnership;

    public function getByParty(int $tenantId, string $partyId): array;

    public function getByAsset(int $tenantId, string $assetId): array;

    public function getByTenant(int $tenantId, int $perPage = 15, int $page = 1): mixed;
}
