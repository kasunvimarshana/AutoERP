<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\Asset;

interface FindAssetServiceInterface
{
    public function findById(int $tenantId, int $id): Asset;

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array;
}
