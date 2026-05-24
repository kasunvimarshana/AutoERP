<?php

declare(strict_types=1);

namespace Modules\VehicleService\Domain\Exceptions;

use RuntimeException;

class VehicleServiceRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string|null $id): self
    {
        return new self("{$resource} [{$id}] was not found.");
    }
}
