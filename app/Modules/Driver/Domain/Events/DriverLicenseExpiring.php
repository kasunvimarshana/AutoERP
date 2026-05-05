<?php

declare(strict_types=1);

namespace Modules\Driver\Domain\Events;

class DriverLicenseExpiring
{
    public function __construct(
        public readonly int $licenseId,
        public readonly int $driverId,
        public readonly int $tenantId,
        public readonly string $expiryDate,
    ) {}
}
