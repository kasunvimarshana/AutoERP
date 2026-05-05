<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\Entities;

class VehicleType
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $baseDailyRate,
        public readonly string $baseHourlyRate,
        public readonly int $seatingCapacity,
        public readonly bool $isActive,
        public readonly ?int $orgUnitId = null,
        public readonly ?int $id = null,
    ) {}
}
