<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\UoM\Domain\Entities\UnitOfMeasure;

interface UnitOfMeasureRepositoryInterface extends RepositoryInterface
{
    public function save(UnitOfMeasure $unit): UnitOfMeasure;

    public function findByCode(int $tenantId, string $code): ?UnitOfMeasure;

    public function findByCategory(int $tenantId, int $categoryId): Collection;

    public function findBaseUnit(int $tenantId, int $categoryId): ?UnitOfMeasure;
}
