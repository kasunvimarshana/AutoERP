<?php
declare(strict_types=1);
namespace Modules\Asset\Domain\RepositoryInterfaces;
use Modules\Asset\Domain\Entities\AssetDepreciation;
interface AssetDepreciationRepositoryInterface {
    public function findById(int $id): ?AssetDepreciation;
    public function findByAsset(int $assetId): array;
    public function findByPeriod(int $tenantId, int $year, int $month): array;
    public function create(array $data): AssetDepreciation;
}
