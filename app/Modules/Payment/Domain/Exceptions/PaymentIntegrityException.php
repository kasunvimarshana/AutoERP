<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Exceptions;

use RuntimeException;

class PaymentIntegrityException extends RuntimeException
{
    public static function rule(string $message): self
    {
        return new self($message);
    }

    public static function conflict(string $message): self
    {
        return new self($message);
    }
}
