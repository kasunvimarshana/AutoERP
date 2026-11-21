<?php

declare(strict_types=1);

namespace Modules\VehicleService\DTOs;

final readonly class VehicleServiceJobTypeData
{
    public function __construct(
        public int $tenantId,
        public string $code,
        public string $name,
        public ?int $organizationUnitId = null,
        public ?string $description = null,
        public bool $tracksMileage = false,
        public bool $isActive = true,
        public int $sortOrder = 0,
        public ?int $actorId = null,
        public ?int $expectedVersion = null,
    ) {}
}
