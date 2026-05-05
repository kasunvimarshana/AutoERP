<?php

declare(strict_types=1);

namespace Modules\Driver\Domain\Events;

use Modules\Driver\Domain\ValueObjects\DriverStatus;

class DriverStatusChanged
{
    public function __construct(
        public readonly int $driverId,
        public readonly int $tenantId,
        public readonly DriverStatus $oldStatus,
        public readonly DriverStatus $newStatus,
    ) {}
}
