<?php

declare(strict_types=1);

namespace Modules\Vehicle\DTOs;

final readonly class VehicleModelData
{
    public function __construct(
        public int $tenantId,
        public int $vehicleMakeId,
        public string $code,
        public string $name,
        public ?int $organizationUnitId = null,
        public ?int $yearFrom = null,
        public ?int $yearTo = null,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}
}
