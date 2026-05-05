<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalRateCard;

interface CreateRentalRateCardServiceInterface
{
    public function execute(array $data): RentalRateCard;
}
