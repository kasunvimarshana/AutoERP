<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalAgreement;
use Modules\Rental\Domain\Entities\RentalTransaction;

interface CalculateRentalChargeServiceInterface
{
    public function execute(RentalAgreement $agreement, RentalTransaction $transaction): string;
}
