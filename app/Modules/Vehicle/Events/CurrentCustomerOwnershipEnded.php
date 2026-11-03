<?php

declare(strict_types=1);

namespace Modules\Vehicle\Events;

final class CurrentCustomerOwnershipEnded
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $vehicleId,
        public readonly int $customerId,
    ) {}
}
