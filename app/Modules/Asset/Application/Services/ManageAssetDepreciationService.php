<?php declare(strict_types=1);

namespace Modules\Asset\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Asset\Application\Contracts\ManageAssetDepreciationServiceInterface;
use Modules\Asset\Domain\Entities\AssetDepreciation;
use Modules\Asset\Domain\RepositoryInterfaces\AssetDepreciationRepositoryInterface;

class ManageAssetDepreciationService implements ManageAssetDepreciationServiceInterface
{
    public function __construct(
        private readonly AssetDepreciationRepositoryInterface $depreciations,
    ) {}

    public function list(int $tenantId, int $perPage = 15, int $page = 1): array
    {
        return $this->depreciations->getAllByTenant((string) $tenantId, $page, $perPage);
    }

    public function getPending(int $tenantId): array
    {
        return $this->depreciations->getPending((string) $tenantId);
    }

    public function post(int $tenantId, string $depreciationId): AssetDepreciation
    {
        return DB::transaction(function () use ($tenantId, $depreciationId): AssetDepreciation {
            $depreciation = $this->depreciations->findById($depreciationId);
            if (!$depreciation || $depreciation->getTenantId() !== (string) $tenantId) {
                throw new \Exception('Depreciation record not found');
            }

            $depreciation->markAsPosted();
            $this->depreciations->update($depreciation);
            return $this->depreciations->findById($depreciationId);
        });
    }
}
