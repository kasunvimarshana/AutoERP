<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments;

use Modules\Core\Application\Results\Result;

interface UpdateVehicleDocumentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
