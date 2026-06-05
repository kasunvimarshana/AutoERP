<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface VehicleServiceWorkflowServiceInterface
{
    public function transition(int|string $jobCardId, array $payload): Result;
}
