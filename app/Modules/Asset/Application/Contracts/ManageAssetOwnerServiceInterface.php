<?php declare(strict_types=1);

namespace Modules\Asset\Application\Contracts;

use Modules\Asset\Domain\Entities\AssetOwner;

interface ManageAssetOwnerServiceInterface
{
    public function create(array $data): AssetOwner;

    public function update(int $tenantId, string $id, array $data): AssetOwner;

    public function find(int $tenantId, string $id): AssetOwner;

    public function delete(int $tenantId, string $id): void;

    public function list(int $tenantId, int $perPage = 15, int $page = 1): array;
}
