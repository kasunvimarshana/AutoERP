<?php

declare(strict_types=1);

namespace Modules\Fleet\Application\Services;

use Modules\Fleet\Application\Contracts\VehicleTypeServiceInterface;
use Modules\Fleet\Application\DTOs\CreateVehicleTypeDTO;
use Modules\Fleet\Domain\Entities\VehicleType;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleTypeRepositoryInterface;

class VehicleTypeService implements VehicleTypeServiceInterface
{
    public function __construct(
        private readonly VehicleTypeRepositoryInterface $repo,
    ) {}

    public function create(CreateVehicleTypeDTO $dto): VehicleType
    {
        $entity = new VehicleType(
            tenantId:        $dto->tenantId,
            name:            $dto->name,
            description:     $dto->description,
            baseDailyRate:   $dto->baseDailyRate,
            baseHourlyRate:  $dto->baseHourlyRate,
            seatingCapacity: $dto->seatingCapacity,
            isActive:        $dto->isActive,
            orgUnitId:       $dto->orgUnitId,
        );

        return $this->repo->save($entity);
    }

    public function update(int $id, array $data): VehicleType
    {
        $entity = $this->repo->find($id);

        if ($entity === null) {
            throw new \RuntimeException("VehicleType {$id} not found.");
        }

        $updated = new VehicleType(
            tenantId:        $entity->tenantId,
            name:            $data['name'] ?? $entity->name,
            description:     $data['description'] ?? $entity->description,
            baseDailyRate:   $data['base_daily_rate'] ?? $entity->baseDailyRate,
            baseHourlyRate:  $data['base_hourly_rate'] ?? $entity->baseHourlyRate,
            seatingCapacity: $data['seating_capacity'] ?? $entity->seatingCapacity,
            isActive:        $data['is_active'] ?? $entity->isActive,
            orgUnitId:       $entity->orgUnitId,
            id:              $entity->id,
        );

        return $this->repo->save($updated);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    public function find(int $id): ?VehicleType
    {
        return $this->repo->find($id);
    }

    public function listByTenant(int $tenantId): array
    {
        return $this->repo->listByTenant($tenantId);
    }
}
