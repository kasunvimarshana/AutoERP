<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\UoM\Domain\Entities\UnitOfMeasure;

interface FindUnitOfMeasureServiceInterface extends ReadServiceInterface
{
    public function findByCode(int $tenantId, string $code): ?UnitOfMeasure;

    public function findByCategory(int $tenantId, int $categoryId): Collection;

    public function findBaseUnit(int $tenantId, int $categoryId): ?UnitOfMeasure;
}
