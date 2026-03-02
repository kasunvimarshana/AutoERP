<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Repositories;

use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Product\Domain\Contracts\UomRepositoryContract;
use Modules\Product\Domain\Entities\UnitOfMeasure;

/**
 * UOM repository implementation.
 *
 * Tenant-aware data access for UnitOfMeasure.
 * No business logic — data access only.
 */
class UomRepository extends AbstractRepository implements UomRepositoryContract
{
    protected string $modelClass = UnitOfMeasure::class;
}
