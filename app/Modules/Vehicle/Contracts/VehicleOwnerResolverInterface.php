<?php

declare(strict_types=1);

namespace Modules\Vehicle\Contracts;

use Modules\Vehicle\Data\VehicleOwnerSnapshot;
use Modules\Vehicle\Enums\VehicleOwnerType;

interface VehicleOwnerResolverInterface
{
    public const TAG = 'vehicle.owner_resolver';

    public function supports(VehicleOwnerType $type): bool;

    public function resolve(int $tenantId, ?int $organizationUnitId, int $ownerId): VehicleOwnerSnapshot;
}
