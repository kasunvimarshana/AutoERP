<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Exceptions;

use RuntimeException;

class SupplierRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string $id): self
    {
        return new self("{$resource} [{$id}] was not found.");
    }
}
