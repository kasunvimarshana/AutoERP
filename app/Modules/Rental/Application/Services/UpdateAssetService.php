<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Rental\Application\Contracts\UpdateAssetServiceInterface;
use Modules\Rental\Domain\Entities\Asset;
use Modules\Rental\Domain\Exceptions\AssetNotFoundException;
use Modules\Rental\Domain\RepositoryInterfaces\AssetRepositoryInterface;

class UpdateAssetService implements UpdateAssetServiceInterface
{
    public function __construct(
        private readonly AssetRepositoryInterface $assetRepository,
    ) {}

    public function execute(int $tenantId, int $id, array $data): Asset
    {
        $asset = $this->assetRepository->findById($tenantId, $id);
        if ($asset === null) {
            throw new AssetNotFoundException($id);
        }

        $asset->update($data);

        return DB::transaction(fn (): Asset => $this->assetRepository->save($asset));
    }
}
