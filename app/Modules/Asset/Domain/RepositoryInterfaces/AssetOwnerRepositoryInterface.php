<?php declare(strict_types=1);

namespace Modules\Asset\Domain\RepositoryInterfaces;

use Modules\Asset\Domain\Entities\AssetOwner;

interface AssetOwnerRepositoryInterface
{
    public function create(AssetOwner $owner): void;
    public function findById(string $id): ?AssetOwner;
    public function findByName(string $tenantId, string $name): ?AssetOwner;
    public function getAllByTenant(string $tenantId, int $page = 1, int $limit = 50): array;
    public function getActiveByTenant(string $tenantId, int $page = 1, int $limit = 50): array;
    public function getThirdPartyOwners(string $tenantId, int $page = 1, int $limit = 50): array;
    public function update(AssetOwner $owner): void;
    public function delete(string $id): void;
    public function countByTenant(string $tenantId): int;
}
