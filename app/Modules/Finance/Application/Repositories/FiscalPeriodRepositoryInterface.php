<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface FiscalPeriodRepositoryInterface extends RepositoryPortInterface
{
    public function findOpenByDate(int $tenantId, string $date, ?int $organizationUnitId = null): ?DataRecord;
}
