<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalSettlement;

interface CreateRentalSettlementServiceInterface
{
    public function execute(array $data): RentalSettlement;
}
