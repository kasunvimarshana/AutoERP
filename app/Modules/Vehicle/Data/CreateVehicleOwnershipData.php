<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Vehicle\Data;

use Modules\Vehicle\Enums\VehicleOwnershipType;
use Modules\Vehicle\Enums\VehicleOwnerType;

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
