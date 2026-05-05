<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Rental\Application\Contracts\CalculateRentalChargeServiceInterface;
use Modules\Rental\Domain\Entities\RentalAgreement;
use Modules\Rental\Domain\Entities\RentalTransaction;

class CalculateRentalChargeService implements CalculateRentalChargeServiceInterface
{
    public function execute(RentalAgreement $agreement, RentalTransaction $transaction): string
    {
        $checkedInTimestamp = (float) $transaction->getCheckedInAt()?->getTimestamp();
        $checkedOutTimestamp = (float) $transaction->getCheckedOutAt()->getTimestamp();
        $hours = max(1.0, ($checkedInTimestamp - $checkedOutTimestamp) / 3600);

        $distance = max(0, ((int) $transaction->getOdometerIn()) - $transaction->getOdometerOut());

        $base = bcmul((string) $hours, '1.000000', 6);

        if ($agreement->getMileagePolicy() === 'with_distance_charge') {
            $distanceCharge = bcmul((string) $distance, '0.100000', 6);
            return bcadd($base, $distanceCharge, 6);
        }

        return $base;
    }
}
