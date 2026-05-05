<?php declare(strict_types=1);

namespace Modules\Asset\Domain\RepositoryInterfaces;

use Modules\Asset\Domain\Entities\AssetDepreciation;

interface AssetDepreciationRepositoryInterface
{
    public function create(AssetDepreciation $depreciation): void;
    public function findById(string $id): ?AssetDepreciation;
    public function getByAsset(string $assetId, int $page = 1, int $limit = 50): array;
    public function getPending(string $tenantId): array;
    public function getByYear(string $tenantId, int $year): array;
    public function getLatestByAsset(string $assetId): ?AssetDepreciation;
    public function update(AssetDepreciation $depreciation): void;
    public function delete(string $id): void;
}
