<?php

declare(strict_types=1);

namespace Modules\Vehicle\DTOs;

use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleOwnershipType;

final readonly class VehicleOwnershipData
{
    public function __construct(
        public VehicleOwnerType $ownerType,
        public ?int $ownerId,
        public VehicleOwnershipType $ownershipType,
        public string $startedAt,
        public ?string $endedAt = null,
        public bool $isCurrent = true,
        public ?string $notes = null,
    ) {}
}
