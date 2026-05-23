<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\Exceptions;

use RuntimeException;

class VehicleRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string $id): self
    {
        return new self(sprintf('%s record [%s] was not found.', $resource, $id));
    }
}
