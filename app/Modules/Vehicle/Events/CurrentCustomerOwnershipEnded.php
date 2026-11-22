<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Vehicle\Events;

final class CurrentCustomerOwnershipEnded
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $vehicleId,
        public readonly int $customerId,
    ) {}
}
