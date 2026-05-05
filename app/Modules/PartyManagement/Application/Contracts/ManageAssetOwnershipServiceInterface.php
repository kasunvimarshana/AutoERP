<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Application\Contracts;

use Modules\PartyManagement\Domain\Entities\AssetOwnership;

interface ManageAssetOwnershipServiceInterface
{
    public function create(array $data): AssetOwnership;

    public function update(int $tenantId, string $id, array $data): AssetOwnership;

    public function find(int $tenantId, string $id): AssetOwnership;

    public function listByParty(int $tenantId, string $partyId): array;

    public function listByAsset(int $tenantId, string $assetId): array;

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed;
}
