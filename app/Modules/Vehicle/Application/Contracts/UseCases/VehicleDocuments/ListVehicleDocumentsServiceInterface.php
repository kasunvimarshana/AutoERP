<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments;

use Modules\Core\Application\Results\Result;

interface ListVehicleDocumentsServiceInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function execute(array $filters): Result;
}
