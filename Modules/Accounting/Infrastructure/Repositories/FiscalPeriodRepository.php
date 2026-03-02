<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Repositories;

use Modules\Accounting\Domain\Contracts\FiscalPeriodRepositoryContract;
use Modules\Accounting\Domain\Entities\FiscalPeriod;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;

/**
 * FiscalPeriod repository implementation.
 *
 * Tenant-aware data access for FiscalPeriod.
 * No business logic — data access only.
 */
class FiscalPeriodRepository extends AbstractRepository implements FiscalPeriodRepositoryContract
{
    protected string $modelClass = FiscalPeriod::class;
}
