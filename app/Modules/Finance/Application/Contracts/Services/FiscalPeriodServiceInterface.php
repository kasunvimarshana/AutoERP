<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;

interface FiscalPeriodServiceInterface
{
    /** @return Result<DataRecord> */
    public function requireOpenPeriod(int $tenantId, string $date, ?int $organizationUnitId = null): Result;
}
