<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Domain\Exceptions;

use RuntimeException;

class ReturnRefundNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("ReturnRefund with ID {$id} not found.");
    }
}
