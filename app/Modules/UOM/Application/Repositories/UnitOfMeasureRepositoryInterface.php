<?php

declare(strict_types=1);

namespace Modules\UOM\Application\Repositories;

use Modules\Core\Application\Contracts\RepositoryPortInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;

interface UnitOfMeasureRepositoryInterface extends RepositoryPortInterface
{
    public function findBySymbol(string $symbol, int $tenantId): ?DataRecord;

    public function findBaseUomForType(string $type, int $tenantId): ?DataRecord;

    public function searchByNameOrSymbol(string $search, int $tenantId, int $perPage, int $page): PagedResult;
}
