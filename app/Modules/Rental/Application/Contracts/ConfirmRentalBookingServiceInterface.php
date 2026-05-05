<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalBooking;

interface ConfirmRentalBookingServiceInterface
{
    public function execute(int $tenantId, int $id): RentalBooking;
}
