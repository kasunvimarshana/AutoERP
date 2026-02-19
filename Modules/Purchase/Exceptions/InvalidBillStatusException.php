<?php

declare(strict_types=1);

namespace Modules\Purchase\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

class InvalidBillStatusException extends BusinessRuleException
{
    public function __construct(string $message, string $currentStatus)
    {
        parent::__construct(
            "{$message} Current status: {$currentStatus}",
            'INVALID_BILL_STATUS',
            422
        );
    }
}
