<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\Exceptions;

use RuntimeException;

class PaymentNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Payment [{$id}] not found.");
    }
}
