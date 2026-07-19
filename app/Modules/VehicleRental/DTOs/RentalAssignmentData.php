<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalAssignmentSide;

final readonly class RentalAssignmentData
{
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public int $agreementId,
        public int $vehicleId,
        public RentalAssignmentSide $side,
        public string $startsAt,
        public ?string $endsAt,
        public ?int $sourceAssignmentId,
        public ?string $handoverOdometer,
        public ?int $driverEmployeeId,
        public bool $selfDrive,
        public ?int $actorId,
    ) {}
}
