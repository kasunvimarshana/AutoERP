<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use RuntimeException;

class InventoryRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string|null $id): self
    {
        return new self("{$resource} [{$id}] was not found.");
    }
}
