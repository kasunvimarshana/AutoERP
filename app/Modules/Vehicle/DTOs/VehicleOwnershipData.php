<?php

declare(strict_types=1);

namespace Modules\Vehicle\DTOs;

use Modules\Vehicle\Enums\VehicleOwnershipType;

final readonly class VehicleOwnershipData
{
    public function __construct(
        public VehicleOwnershipType $ownershipType,
        public string $startedAt,
        public ?string $ownerType = null,
        public ?int $ownerId = null,
        public ?int $customerId = null,
        public ?string $endedAt = null,
        public bool $isCurrent = true,
        public ?string $notes = null,
    ) {}
}
