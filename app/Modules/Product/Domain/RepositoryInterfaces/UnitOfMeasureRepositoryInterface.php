<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface UnitOfMeasureRepositoryInterface extends RepositoryInterface
{
    public function findByAbbreviation(string $abbreviation, int $tenantId): mixed;

    public function findByTenant(int $tenantId): mixed;

    public function findBaseUnits(int $tenantId): mixed;
}
