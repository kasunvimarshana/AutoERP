<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\Entities;

use DateTimeImmutable;
use Modules\ServiceCenter\Domain\ValueObjects\JobType;
use Modules\ServiceCenter\Domain\ValueObjects\ServiceJobStatus;

readonly class ServiceJob
{
    public function __construct(
        public ?int $id,
        public int $tenantId,
        public ?int $orgUnitId,
        public int $vehicleId,
        public ?int $driverId,
        public string $jobNumber,
        public JobType $jobType,
        public ServiceJobStatus $status,
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
        public bool $isActive,
        public int $rowVersion,
    ) {}
}
