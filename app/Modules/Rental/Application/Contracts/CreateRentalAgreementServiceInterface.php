<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalAgreement;

interface CreateRentalAgreementServiceInterface
{
    public function execute(
        string $tenantId,
        string $reservationId,
        ?string $digitalAgreementUrl,
        string $securityDeposit,
        string $currencyCode,
        string $fuelPolicy,
        string $mileagePolicy,
    ): RentalAgreement;
}
