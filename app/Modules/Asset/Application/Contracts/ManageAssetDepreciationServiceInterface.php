<?php declare(strict_types=1);

namespace Modules\Asset\Application\Contracts;

use Modules\Asset\Domain\Entities\AssetDepreciation;

interface ManageAssetDepreciationServiceInterface
{
    public function list(int $tenantId, int $perPage = 15, int $page = 1): array;

    public function getPending(int $tenantId): array;

    public function post(int $tenantId, string $depreciationId): AssetDepreciation;
}
