<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class FiscalPeriodAlreadyExistsException extends DomainException
{
    public function __construct(int $tenantId, int $fiscalYearId, int $periodNumber)
    {
        parent::__construct(sprintf(
            'Fiscal period number %d already exists for tenant %d in fiscal year %d.',
            $periodNumber,
            $tenantId,
            $fiscalYearId,
        ), 409);
    }
}
