<?php

declare(strict_types=1);

namespace Modules\UOM\Domain\Exceptions;

use RuntimeException;

class UomIntegrityException extends RuntimeException
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
