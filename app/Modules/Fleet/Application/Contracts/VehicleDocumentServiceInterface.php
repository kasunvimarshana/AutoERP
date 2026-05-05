<?php

declare(strict_types=1);

namespace Modules\Fleet\Application\Contracts;

use Modules\Fleet\Application\DTOs\CreateVehicleDocumentDTO;
use Modules\Fleet\Domain\Entities\VehicleDocument;

interface VehicleDocumentServiceInterface
{
    public function create(CreateVehicleDocumentDTO $dto): VehicleDocument;

    public function update(int $id, array $data): VehicleDocument;

    public function delete(int $id): void;

    public function find(int $id): ?VehicleDocument;

    /** @return list<VehicleDocument> */
    public function listByVehicle(int $vehicleId): array;

    /** @return list<VehicleDocument> */
    public function listExpiringSoon(int $tenantId, int $days = 30): array;
}
