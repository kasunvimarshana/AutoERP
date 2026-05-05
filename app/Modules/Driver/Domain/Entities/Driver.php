<?php

declare(strict_types=1);

namespace Modules\Driver\Domain\Entities;

use Modules\Driver\Domain\ValueObjects\CompensationType;
use Modules\Driver\Domain\ValueObjects\DriverStatus;

class Driver
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly ?int $orgUnitId,
        public readonly ?int $employeeId,
        public string $driverCode,
        public string $fullName,
        public ?string $phone,
        public ?string $email,
        public ?string $address,
        public CompensationType $compensationType,
        public string $perTripRate,
        public string $commissionPct,
        public DriverStatus $status,
        public ?array $metadata,
        public bool $isActive,
    ) {}
}
