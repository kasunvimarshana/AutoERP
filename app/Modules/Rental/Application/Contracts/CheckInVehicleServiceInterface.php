<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Application\DTOs\CheckInRentalDTO;
use Modules\Rental\Domain\Entities\RentalTransaction;

interface CheckInVehicleServiceInterface
{
    public function execute(CheckInRentalDTO $dto): RentalTransaction;
}
