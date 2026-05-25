<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments;

use Modules\Core\Application\Results\Result;

interface GetVehicleDocumentServiceInterface
{
    public function execute(int|string $id): Result;
}
