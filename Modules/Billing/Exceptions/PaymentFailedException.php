<?php

declare(strict_types=1);

namespace Modules\Billing\Exceptions;

use Modules\Core\Exceptions\DomainException;

class PaymentFailedException extends DomainException
{
    public function __construct(string $reason)
    {
        parent::__construct("Payment failed: {$reason}");
    }
}
