<?php

declare(strict_types=1);

namespace Modules\Fleet\Application\DTOs;

final class CreateVehicleTypeDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly string $baseDailyRate = '0.000000',
        public readonly string $baseHourlyRate = '0.000000',
        public readonly int $seatingCapacity = 1,
        public readonly bool $isActive = true,
        public readonly ?int $orgUnitId = null,
    ) {}
}
