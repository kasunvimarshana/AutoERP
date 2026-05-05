<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Application\Contracts;

use Modules\ServiceCenter\Application\DTOs\CreateServiceJobDTO;
use Modules\ServiceCenter\Application\DTOs\UpdateServiceJobDTO;
use Modules\ServiceCenter\Domain\Entities\ServiceJob;
use Modules\ServiceCenter\Domain\ValueObjects\ServiceJobStatus;

interface ServiceJobServiceInterface
{
    public function getById(int $id, int $tenantId): ServiceJob;

    public function listByTenant(int $tenantId, array $filters = []): array;

    public function listByVehicle(int $vehicleId, int $tenantId): array;

    public function create(CreateServiceJobDTO $dto): ServiceJob;

    public function update(int $id, int $tenantId, UpdateServiceJobDTO $dto): ServiceJob;

    public function changeStatus(int $id, int $tenantId, ServiceJobStatus $status): ServiceJob;

    public function delete(int $id, int $tenantId): void;
}
