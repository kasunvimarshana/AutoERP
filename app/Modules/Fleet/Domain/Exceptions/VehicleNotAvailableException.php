<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\Exceptions;

use RuntimeException;

class VehicleNotAvailableException extends RuntimeException
{
    public static function notRentable(string $registration): self
    {
        return new self("Vehicle '{$registration}' is not rentable or not in available state.");
    }

    public static function notServiceable(string $registration): self
    {
        return new self("Vehicle '{$registration}' is not serviceable or not in available state.");
    }
}
