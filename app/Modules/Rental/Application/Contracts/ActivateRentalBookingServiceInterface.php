<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

interface ActivateRentalBookingServiceInterface
{
    public function execute(array $data): mixed;
}
