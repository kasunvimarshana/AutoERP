<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Exceptions;

use RuntimeException;

class HRIntegrityException extends RuntimeException
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
