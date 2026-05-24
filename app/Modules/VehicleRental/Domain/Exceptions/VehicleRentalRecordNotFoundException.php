<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Domain\Exceptions;

use RuntimeException;

class VehicleRentalRecordNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string|null $id): self
    {
        return new self("{$resource} [{$id}] was not found.");
    }
}
