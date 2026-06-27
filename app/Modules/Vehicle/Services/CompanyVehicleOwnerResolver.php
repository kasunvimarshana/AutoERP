<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use InvalidArgumentException;
use Modules\Vehicle\Contracts\VehicleOwnerResolverInterface;
use Modules\Vehicle\DTOs\VehicleOwnerSnapshot;
use Modules\Vehicle\Enums\VehicleOwnerType;

final class CompanyVehicleOwnerResolver implements VehicleOwnerResolverInterface
{
    private const OWNER_CODE = 'COMPANY';
    private const OWNER_NAME = 'Company Fleet';

    public function supports(VehicleOwnerType $type): bool
    {
        return $type === VehicleOwnerType::Company;
    }

    public function resolve(
        VehicleOwnerType $type,
        ?int $ownerId,
        int $tenantId,
        ?int $organizationUnitId,
    ): VehicleOwnerSnapshot {
        if (! $this->supports($type) || $ownerId !== null) {
            throw new InvalidArgumentException('Company vehicle ownership must not reference a party identifier.');
        }

        return new VehicleOwnerSnapshot($type, null, self::OWNER_CODE, self::OWNER_NAME);
    }
}
