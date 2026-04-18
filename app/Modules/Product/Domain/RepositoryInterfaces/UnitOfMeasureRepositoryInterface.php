<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\UnitOfMeasure;

interface UnitOfMeasureRepositoryInterface extends RepositoryInterface
{
    public function save(UnitOfMeasure $unitOfMeasure): UnitOfMeasure;

    public function findByTenantAndSymbol(int $tenantId, string $symbol): ?UnitOfMeasure;

    public function find($id, array $columns = ['*']): ?UnitOfMeasure;
}
