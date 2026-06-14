<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalFinancialSide: string
{
    case Revenue = 'revenue';
    case Cost = 'cost';

    public static function fromDirection(RentalAgreementDirection $direction): self
    {
        return $direction === RentalAgreementDirection::Outbound ? self::Revenue : self::Cost;
    }
}
