<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services\Ownership;

use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Models\VehicleOwnership;

final class CompanyVehicleCoverageService
{
    // Caller holds the physical Vehicle lock. Rental must not infer company ownership from a missing supplier.
    public function covers(int $tenant, int $vehicle, string $start, ?string $end): bool
    {
        return VehicleOwnership::query()->forTenant($tenant)->where('vehicle_id', $vehicle)
            ->where('owner_type', VehicleOwnerType::Company->value)->where('started_at', '<=', $start)
            ->where(fn ($q) => $q->whereNull('ended_at')->when($end !== null, fn ($range) => $range->orWhere('ended_at', '>=', $end)))->lockForUpdate()->first(['id']) !== null;
    }
}
