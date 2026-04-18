<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class FiscalYearAlreadyExistsException extends DomainException
{
    public function __construct(int $tenantId, string $name)
    {
        parent::__construct(sprintf(
            'Fiscal year "%s" already exists for tenant %d.',
            $name,
            $tenantId,
        ), 409);
    }
}
