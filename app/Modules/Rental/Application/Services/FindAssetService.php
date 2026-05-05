<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Rental\Application\Contracts\FindAssetServiceInterface;
use Modules\Rental\Domain\Entities\Asset;
use Modules\Rental\Domain\Exceptions\AssetNotFoundException;
use Modules\Rental\Domain\RepositoryInterfaces\AssetRepositoryInterface;

class FindAssetService implements FindAssetServiceInterface
{
    public function __construct(
        private readonly AssetRepositoryInterface $assetRepository,
    ) {}

    public function findById(int $tenantId, int $id): Asset
    {
        $asset = $this->assetRepository->findById($tenantId, $id);
        if ($asset === null) {
            throw new AssetNotFoundException($id);
        }

        return $asset;
    }

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array
    {
        return $this->assetRepository->paginate($tenantId, $filters, $perPage, $page);
    }
}
