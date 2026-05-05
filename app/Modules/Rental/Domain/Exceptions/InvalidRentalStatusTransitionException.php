<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Exceptions;

use RuntimeException;

class InvalidRentalStatusTransitionException extends RuntimeException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("Cannot transition rental status from [{$from}] to [{$to}].");
    }
}
