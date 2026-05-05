<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Application\DTOs\CheckOutRentalDTO;
use Modules\Rental\Domain\Entities\RentalTransaction;

interface CheckOutVehicleServiceInterface
{
    public function execute(CheckOutRentalDTO $dto): RentalTransaction;
}
