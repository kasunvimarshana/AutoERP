<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments;

use Modules\Core\Application\Results\Result;

interface CreateVehicleDocumentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
