<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\Asset;

interface AssetRepositoryInterface
{
    public function save(Asset $asset): Asset;

    public function findById(int $tenantId, int $id): ?Asset;

    public function findByCode(int $tenantId, string $assetCode): ?Asset;

    /** @return array{data: Asset[], total: int, per_page: int, current_page: int} */
    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array;

    public function existsByCode(int $tenantId, string $assetCode, ?int $excludeId = null): bool;
}
