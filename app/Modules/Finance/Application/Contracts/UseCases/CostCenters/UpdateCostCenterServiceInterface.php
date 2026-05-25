<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\CostCenters;

use Modules\Core\Application\Results\Result;

interface UpdateCostCenterServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
