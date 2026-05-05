<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\Exceptions;

use RuntimeException;

class InvalidStateTransitionException extends RuntimeException
{
    public static function from(string $from, string $to, string $registration): self
    {
        return new self("Cannot transition vehicle '{$registration}' from '{$from}' to '{$to}'.");
    }
}
