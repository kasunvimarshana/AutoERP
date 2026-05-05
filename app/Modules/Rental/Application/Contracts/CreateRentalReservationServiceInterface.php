<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Application\DTOs\CreateRentalReservationDTO;
use Modules\Rental\Domain\Entities\RentalReservation;

interface CreateRentalReservationServiceInterface
{
    public function execute(CreateRentalReservationDTO $dto): RentalReservation;
}
