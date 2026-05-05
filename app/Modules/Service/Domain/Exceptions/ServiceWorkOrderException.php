<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Exceptions;

class ServiceWorkOrderException extends \RuntimeException
{
    public static function invalidTransition(string $from, string $to): self
    {
        return new self("Cannot transition work order from '{$from}' to '{$to}'.");
    }

    public static function notFound(int $id): self
    {
        return new self("Service work order #{$id} not found.");
    }
}
