<?php

declare(strict_types=1);

namespace Modules\Driver\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Driver\Application\Contracts\DriverServiceInterface;
use Modules\Driver\Application\DTOs\CreateDriverDTO;
use Modules\Driver\Application\DTOs\UpdateDriverDTO;
use Modules\Driver\Domain\Entities\Driver;
use Modules\Driver\Domain\Exceptions\DriverNotFoundException;
use Modules\Driver\Domain\RepositoryInterfaces\DriverRepositoryInterface;
use Modules\Driver\Domain\ValueObjects\CompensationType;
use Modules\Driver\Domain\ValueObjects\DriverStatus;

class DriverService implements DriverServiceInterface
{
    public function __construct(
        private readonly DriverRepositoryInterface $drivers,
    ) {}

    public function getById(int $id): Driver
    {
        return $this->drivers->findById($id)
            ?? throw new DriverNotFoundException($id);
    }

    public function listByTenant(int $tenantId, ?int $orgUnitId = null): array
    {
        return $this->drivers->findByTenant($tenantId, $orgUnitId);
    }

    public function listAvailableForTrip(int $tenantId, ?int $orgUnitId = null): array
    {
        return $this->drivers->findAvailableForTrip($tenantId, $orgUnitId);
    }

    public function create(CreateDriverDTO $dto): Driver
    {
        return DB::transaction(function () use ($dto): Driver {
            $driver = new Driver(
                id: null,
                tenantId: $dto->tenantId,
                orgUnitId: $dto->orgUnitId,
                employeeId: $dto->employeeId,
                driverCode: $dto->driverCode,
                fullName: $dto->fullName,
                phone: $dto->phone,
                email: $dto->email,
                address: $dto->address,
                compensationType: $dto->compensationType,
                perTripRate: $dto->perTripRate,
                commissionPct: $dto->commissionPct,
                status: DriverStatus::Available,
                metadata: $dto->metadata,
                isActive: true,
            );

            return $this->drivers->save($driver);
        });
    }

    public function update(int $id, UpdateDriverDTO $dto): Driver
    {
        return DB::transaction(function () use ($id, $dto): Driver {
            $driver = $this->getById($id);

            $driver->fullName        = $dto->fullName;
            $driver->phone           = $dto->phone;
            $driver->email           = $dto->email;
            $driver->address         = $dto->address;
            $driver->compensationType = $dto->compensationType;
            $driver->perTripRate     = $dto->perTripRate;
            $driver->commissionPct   = $dto->commissionPct;
            $driver->metadata        = $dto->metadata;
            $driver->isActive        = $dto->isActive;

            return $this->drivers->save($driver);
        });
    }

    public function changeStatus(int $id, DriverStatus $status): Driver
    {
        return DB::transaction(function () use ($id, $status): Driver {
            $driver = $this->getById($id);
            $this->drivers->updateStatus($id, $status);
            $driver->status = $status;

            return $driver;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->getById($id);
            $this->drivers->delete($id);
        });
    }
}
