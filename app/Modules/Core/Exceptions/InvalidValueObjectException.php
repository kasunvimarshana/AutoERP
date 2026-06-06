<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

final class InvalidValueObjectException extends DomainException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
