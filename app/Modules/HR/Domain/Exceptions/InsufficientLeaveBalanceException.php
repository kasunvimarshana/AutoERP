<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Exceptions;

class InsufficientLeaveBalanceException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Insufficient leave balance for the requested days.');
    }
}
