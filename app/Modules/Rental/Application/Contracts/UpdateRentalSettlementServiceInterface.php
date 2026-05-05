<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalSettlement;

interface UpdateRentalSettlementServiceInterface
{
    public function execute(array $data): RentalSettlement;
}
