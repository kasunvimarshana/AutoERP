<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests\Concerns;

use Modules\Vehicle\Data\VehicleOwnershipDraftData;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleOwnershipType;

trait MapsVehicleOwnershipData
{
    private function mapOwnership(array $row): VehicleOwnershipDraftData
    {
        return new VehicleOwnershipDraftData(
            ownerType: VehicleOwnerType::from((string) $row['owner_type']),
            ownerId: $row['owner_id'] === null ? null : (int) $row['owner_id'],
            ownershipType: VehicleOwnershipType::from((string) $row['ownership_type']),
            startedAt: (string) $row['started_at'],
            endedAt: $row['ended_at'] ?? null,
            isCurrent: (bool) ($row['is_current'] ?? false),
            notes: $row['notes'] ?? null,
        );
    }
}
