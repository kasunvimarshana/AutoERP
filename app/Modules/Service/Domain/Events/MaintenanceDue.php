<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Events;

use Modules\Service\Domain\Entities\ServiceMaintenancePlan;

class MaintenanceDue
{
    public function __construct(
        public readonly ServiceMaintenancePlan $plan,
    ) {}
}
