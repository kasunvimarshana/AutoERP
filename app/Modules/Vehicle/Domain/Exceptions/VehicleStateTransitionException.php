<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class VehicleStateTransitionException extends DomainException
{
    public static function cannotRentWhileInService(string $serviceStatus): self
    {
        return new self('Vehicle cannot be rented while service status is '.$serviceStatus.'.');
    }

    public static function cannotScheduleServiceWhileRented(string $rentalStatus): self
    {
        return new self('Vehicle cannot be scheduled for service while rental status is '.$rentalStatus.'.');
    }

    public static function invalidStatus(string $type, string $status): self
    {
        return new self('Invalid '.$type.' status: '.$status.'.');
    }
}
