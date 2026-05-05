<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalBooking;

interface CreateRentalBookingServiceInterface
{
    public function execute(array $data): RentalBooking;
}
