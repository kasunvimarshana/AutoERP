<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Domain\Exceptions;

use RuntimeException;

class ReturnAlreadyProcessedException extends RuntimeException
{
    public function __construct(int $returnRefundId)
    {
        parent::__construct("ReturnRefund with ID {$returnRefundId} has already been processed.");
    }
}
