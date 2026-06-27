<?php

declare(strict_types=1);

namespace Modules\Vehicle\Contracts;

use Modules\Vehicle\DTOs\VehicleOwnerSnapshot;
use Modules\Vehicle\Enums\VehicleOwnerType;

interface VehicleOwnerResolverInterface
{
    public function supports(VehicleOwnerType $type): bool;

    public function resolve(
        VehicleOwnerType $type,
        ?int $ownerId,
        int $tenantId,
        ?int $organizationUnitId,
    ): VehicleOwnerSnapshot;
}
