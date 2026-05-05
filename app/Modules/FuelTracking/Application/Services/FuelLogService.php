<?php

declare(strict_types=1);

namespace Modules\FuelTracking\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\FuelTracking\Application\Contracts\FuelLogServiceInterface;
use Modules\FuelTracking\Application\DTOs\CreateFuelLogDTO;
use Modules\FuelTracking\Domain\Entities\FuelLog;
use Modules\FuelTracking\Domain\Exceptions\FuelLogNotFoundException;
use Modules\FuelTracking\Domain\RepositoryInterfaces\FuelLogRepositoryInterface;
use Ramsey\Uuid\Uuid;

class FuelLogService implements FuelLogServiceInterface
{
    public function __construct(
        private readonly FuelLogRepositoryInterface $repository,
    ) {}

    public function createLog(CreateFuelLogDTO $dto): FuelLog
    {
        return DB::transaction(function () use ($dto): FuelLog {
            $log = new FuelLog(
                id: Uuid::uuid4()->toString(),
                tenantId: $dto->tenantId,
                orgUnitId: $dto->orgUnitId,
                rowVersion: 1,
                logNumber: $dto->logNumber,
                vehicleId: $dto->vehicleId,
                driverId: $dto->driverId,
                fuelType: $dto->fuelType,
                odoReading: $dto->odoReading,
                litres: $dto->litres,
                costPerLitre: $dto->costPerLitre,
                totalCost: $dto->totalCost,
                stationName: $dto->stationName,
                filledAt: $dto->filledAt,
                notes: $dto->notes,
                metadata: $dto->metadata,
                isActive: true,
                createdAt: null,
                updatedAt: null,
            );

            return $this->repository->save($log);
        });
    }

    public function getLog(string $id): FuelLog
    {
        $log = $this->repository->findById($id);

        if ($log === null) {
            throw new FuelLogNotFoundException($id);
        }

        return $log;
    }

    public function getByTenant(string $tenantId, string $orgUnitId): array
    {
        return $this->repository->findByTenant($tenantId, $orgUnitId);
    }

    public function getByVehicle(string $tenantId, string $vehicleId): array
    {
        return $this->repository->findByVehicle($tenantId, $vehicleId);
    }

    public function getByDriver(string $tenantId, string $driverId): array
    {
        return $this->repository->findByDriver($tenantId, $driverId);
    }

    public function deleteLog(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->repository->delete($id);
        });
    }
}
