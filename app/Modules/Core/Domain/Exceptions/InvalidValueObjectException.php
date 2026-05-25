<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Exceptions;

final class InvalidValueObjectException extends CoreException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
