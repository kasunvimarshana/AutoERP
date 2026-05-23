<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use RuntimeException;

class FinanceIntegrityException extends RuntimeException
{
    public static function rule(string $message): self
    {
        return new self($message);
    }
}
