<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Events;

use Modules\Rental\Domain\Entities\RentalBooking;

class RentalBookingConfirmed
{
    public function __construct(
        public readonly RentalBooking $booking,
    ) {}
}
