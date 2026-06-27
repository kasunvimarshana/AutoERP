<?php

declare(strict_types=1);

namespace Modules\Vehicle\Enums;

enum VehicleOwnerType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';
    case Company = 'company';

    public function allows(VehicleOwnershipType $ownershipType): bool
    {
        return match ($this) {
            self::Customer => $ownershipType === VehicleOwnershipType::CustomerOwned,
            self::Supplier => in_array($ownershipType, [
                VehicleOwnershipType::ThirdParty,
                VehicleOwnershipType::Leased,
                VehicleOwnershipType::Rented,
            ], true),
            self::Company => in_array($ownershipType, [
                VehicleOwnershipType::CompanyOwned,
                VehicleOwnershipType::Owned,
            ], true),
        };
    }
}
