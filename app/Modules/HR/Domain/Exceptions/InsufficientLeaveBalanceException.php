<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class InsufficientLeaveBalanceException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Insufficient leave balance for the requested days.', 422);
    }
}
