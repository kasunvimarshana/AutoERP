<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Application\DTOs;

use DateTimeImmutable;
use Modules\ServiceCenter\Domain\ValueObjects\JobType;

readonly class CreateServiceJobDTO
{
    public function __construct(
        public int $tenantId,
        public ?int $orgUnitId,
        public int $vehicleId,
        public ?int $driverId,
        public string $jobNumber,
        public JobType $jobType,
        public DateTimeImmutable $scheduledAt,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $completedAt,
        public ?string $odometerIn,
        public ?string $odometerOut,
        public ?string $description,
        public string $partsCost,
        public string $labourCost,
        public string $totalCost,
        public ?string $technicianNotes,
        public bool $customerApproval,
        public ?array $metadata,
    ) {}
}
