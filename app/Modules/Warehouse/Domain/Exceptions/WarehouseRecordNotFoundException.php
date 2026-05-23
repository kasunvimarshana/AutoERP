<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Exceptions;

use RuntimeException;

class WarehouseRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string $id): self
    {
        return new self(sprintf('%s record [%s] was not found.', $resource, $id));
    }
}
