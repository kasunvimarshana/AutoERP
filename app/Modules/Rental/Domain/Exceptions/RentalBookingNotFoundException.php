<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Exceptions;

class RentalBookingNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Rental booking with ID {$id} not found.");
    }
}
