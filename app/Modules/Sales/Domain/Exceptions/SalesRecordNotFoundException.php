<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Exceptions;

use RuntimeException;

class SalesRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string|null $id): self
    {
        return new self("{$resource} [{$id}] was not found.");
    }
}
