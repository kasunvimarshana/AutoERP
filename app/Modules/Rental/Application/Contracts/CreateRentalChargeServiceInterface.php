<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalCharge;

interface CreateRentalChargeServiceInterface
{
    public function execute(array $data): RentalCharge;
}
