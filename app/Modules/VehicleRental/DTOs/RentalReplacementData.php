<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

final readonly class RentalReplacementData
{
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public int $vehicleId,
        public string $effectiveAt,
        public ?string $oldReturnOdometer,
        public ?string $newHandoverOdometer,
        public ?int $sourceAssignmentId,
        public ?int $driverEmployeeId,
        public bool $selfDrive,
        public string $reason,
        public ?string $oldFuelLevel,
        public ?string $newFuelLevel,
        public ?string $oldConditionNotes,
        public ?string $newConditionNotes,
        public ?string $oldDamageNotes,
        public ?string $newDamageNotes,
        public ?int $actorId,
    ) {}
}
