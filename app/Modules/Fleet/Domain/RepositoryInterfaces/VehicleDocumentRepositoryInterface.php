<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\RepositoryInterfaces;

use Modules\Fleet\Domain\Entities\VehicleDocument;

interface VehicleDocumentRepositoryInterface
{
    public function find(int $id): ?VehicleDocument;

    /** @return list<VehicleDocument> */
    public function listByVehicle(int $vehicleId): array;

    /** @return list<VehicleDocument> Docs expiring within $days days across tenant */
    public function listExpiringSoon(int $tenantId, int $days = 30): array;

    public function save(VehicleDocument $document): VehicleDocument;

    public function delete(int $id): void;
}
