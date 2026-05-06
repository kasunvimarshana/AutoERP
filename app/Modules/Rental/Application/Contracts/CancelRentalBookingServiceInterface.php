<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

interface CancelRentalBookingServiceInterface
{
    public function execute(array $data): mixed;
}
