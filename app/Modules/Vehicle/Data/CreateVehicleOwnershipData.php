<?php

declare(strict_types=1);

namespace Modules\Vehicle\Data;

use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleOwnershipType;

final readonly class CreateVehicleOwnershipData
{
    public function __construct(
        public int $vehicleId,
        public VehicleOwnerType $ownerType,
        public ?int $ownerId,
        public VehicleOwnershipType $ownershipType,
        public string $startedAt,
        public ?string $endedAt,
        public bool $isCurrent,
        public ?string $notes,
    ) {}
}
