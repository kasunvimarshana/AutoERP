<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnosticLines;

use Modules\Core\Application\Results\Result;

interface ListVehicleServiceDiagnosticLinesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}