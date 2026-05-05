<?php

declare(strict_types=1);

namespace Modules\Service\Application\Contracts;

use Modules\Service\Domain\Entities\ServiceMaintenancePlan;

interface CreateServiceMaintenancePlanServiceInterface
{
    public function execute(array $data): ServiceMaintenancePlan;
}
