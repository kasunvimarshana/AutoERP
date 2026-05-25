<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\CostCenters;

use Modules\Core\Application\Results\Result;

interface ListCostCentersServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
