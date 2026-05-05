<?php

declare(strict_types=1);

namespace Modules\FuelTracking\Application\DTOs;

use DateTimeImmutable;
use Modules\FuelTracking\Domain\ValueObjects\FuelType;

class CreateFuelLogDTO
{
    public function __construct(
        public readonly string            $tenantId,
        public readonly string            $orgUnitId,
        public readonly string            $logNumber,
        public readonly string            $vehicleId,
        public readonly ?string           $driverId,
        public readonly FuelType          $fuelType,
        public readonly string            $odoReading,
        public readonly string            $litres,
        public readonly string            $costPerLitre,
        public readonly string            $totalCost,
        public readonly ?string           $stationName,
        public readonly ?DateTimeImmutable $filledAt,
        public readonly ?string           $notes,
        public readonly ?array            $metadata,
    ) {}
}
