<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Application\DTOs;

use DateTimeImmutable;
use Modules\ServiceCenter\Domain\ValueObjects\JobType;

readonly class UpdateServiceJobDTO
{
    public function __construct(
        public ?JobType $jobType = null,
        public ?DateTimeImmutable $scheduledAt = null,
        public ?DateTimeImmutable $startedAt = null,
        public ?DateTimeImmutable $completedAt = null,
        public ?string $odometerIn = null,
        public ?string $odometerOut = null,
        public ?string $description = null,
        public ?string $partsCost = null,
        public ?string $labourCost = null,
        public ?string $totalCost = null,
        public ?string $technicianNotes = null,
        public ?bool $customerApproval = null,
        public ?array $metadata = null,
    ) {}
}
