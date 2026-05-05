<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\RepositoryInterfaces;

use Modules\ServiceCenter\Domain\Entities\ServiceJob;
use Modules\ServiceCenter\Domain\ValueObjects\ServiceJobStatus;

interface ServiceJobRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?ServiceJob;

    public function findByTenant(int $tenantId, array $filters = []): array;

    public function findByVehicle(int $vehicleId, int $tenantId): array;

    public function save(ServiceJob $serviceJob): ServiceJob;

    public function updateStatus(int $id, int $tenantId, ServiceJobStatus $status): void;

    public function delete(int $id, int $tenantId): void;
}
