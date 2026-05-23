<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Exceptions;

use RuntimeException;

class CustomerIntegrityException extends RuntimeException
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
