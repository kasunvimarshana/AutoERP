<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\RepositoryInterfaces;

interface VehicleDocumentRepositoryInterface
{
    public function upsertByType(int $tenantId, int $vehicleId, string $documentType, array $data): void;

    public function listExpiring(int $tenantId, int $days): iterable;
}
