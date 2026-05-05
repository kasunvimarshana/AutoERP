<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Exceptions;

use RuntimeException;

class VehicleNotAvailableForRentalException extends RuntimeException
{
    public function __construct(int $vehicleId)
    {
        parent::__construct("Vehicle [{$vehicleId}] is not available for rental.");
    }
}
