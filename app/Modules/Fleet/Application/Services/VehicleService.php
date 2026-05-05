<?php

declare(strict_types=1);

namespace Modules\Fleet\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Fleet\Application\Contracts\VehicleServiceInterface;
use Modules\Fleet\Application\DTOs\ChangeVehicleStateDTO;
use Modules\Fleet\Application\DTOs\CreateVehicleDTO;
use Modules\Fleet\Application\DTOs\UpdateVehicleDTO;
use Modules\Fleet\Domain\Entities\Vehicle;
use Modules\Fleet\Domain\Events\VehicleStateChanged;
use Modules\Fleet\Domain\Exceptions\InvalidStateTransitionException;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleRepositoryInterface;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleStateLogRepositoryInterface;
use Modules\Fleet\Domain\ValueObjects\VehicleState;
use Illuminate\Support\Facades\Event;

class VehicleService implements VehicleServiceInterface
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repo,
        private readonly VehicleStateLogRepositoryInterface $stateLogRepo,
    ) {}

    public function create(CreateVehicleDTO $dto): Vehicle
    {
        return DB::transaction(function () use ($dto) {
            $entity = new Vehicle(
                tenantId:                     $dto->tenantId,
                vehicleTypeId:                $dto->vehicleTypeId,
                registrationNumber:           $dto->registrationNumber,
                make:                         $dto->make,
                model:                        $dto->model,
                year:                         $dto->year,
                ownershipType:                $dto->ownershipType,
                isRentable:                   $dto->isRentable,
                isServiceable:                $dto->isServiceable,
                currentState:                 VehicleState::AVAILABLE,
                currentOdometer:              '0.00',
                fuelType:                     $dto->fuelType,
                transmission:                 $dto->transmission,
                seatingCapacity:              $dto->seatingCapacity,
                color:                        $dto->color,
                vinNumber:                    $dto->vinNumber,
                engineNumber:                 $dto->engineNumber,
                ownerSupplierId:              $dto->ownerSupplierId,
                ownerCommissionPct:           $dto->ownerCommissionPct,
                fuelCapacity:                 $dto->fuelCapacity,
                assetAccountId:               $dto->assetAccountId,
                accumDepreciationAccountId:   $dto->accumDepreciationAccountId,
                depreciationExpenseAccountId: $dto->depreciationExpenseAccountId,
                rentalRevenueAccountId:       $dto->rentalRevenueAccountId,
                serviceRevenueAccountId:      $dto->serviceRevenueAccountId,
                acquisitionCost:              $dto->acquisitionCost,
                acquiredAt:                   $dto->acquiredAt,
                disposedAt:                   null,
                metadata:                     $dto->metadata,
                isActive:                     true,
                orgUnitId:                    $dto->orgUnitId,
            );

            return $this->repo->save($entity);
        });
    }

    public function update(UpdateVehicleDTO $dto): Vehicle
    {
        return DB::transaction(function () use ($dto) {
            $entity = $this->repo->find($dto->vehicleId);

            if ($entity === null) {
                throw new \RuntimeException("Vehicle {$dto->vehicleId} not found.");
            }

            $updated = new Vehicle(
                tenantId:                     $entity->tenantId,
                vehicleTypeId:                $dto->vehicleTypeId ?? $entity->vehicleTypeId,
                registrationNumber:           $entity->registrationNumber,
                make:                         $entity->make,
                model:                        $entity->model,
                year:                         $entity->year,
                ownershipType:                $entity->ownershipType,
                isRentable:                   $dto->isRentable ?? $entity->isRentable,
                isServiceable:                $dto->isServiceable ?? $entity->isServiceable,
                currentState:                 $entity->currentState,
                currentOdometer:              $entity->currentOdometer,
                fuelType:                     $entity->fuelType,
                transmission:                 $entity->transmission,
                seatingCapacity:              $entity->seatingCapacity,
                color:                        $dto->color ?? $entity->color,
                vinNumber:                    $entity->vinNumber,
                engineNumber:                 $entity->engineNumber,
                ownerSupplierId:              $dto->ownerSupplierId ?? $entity->ownerSupplierId,
                ownerCommissionPct:           $dto->ownerCommissionPct ?? $entity->ownerCommissionPct,
                fuelCapacity:                 $entity->fuelCapacity,
                assetAccountId:               $dto->assetAccountId ?? $entity->assetAccountId,
                accumDepreciationAccountId:   $dto->accumDepreciationAccountId ?? $entity->accumDepreciationAccountId,
                depreciationExpenseAccountId: $dto->depreciationExpenseAccountId ?? $entity->depreciationExpenseAccountId,
                rentalRevenueAccountId:       $dto->rentalRevenueAccountId ?? $entity->rentalRevenueAccountId,
                serviceRevenueAccountId:      $dto->serviceRevenueAccountId ?? $entity->serviceRevenueAccountId,
                acquisitionCost:              $dto->acquisitionCost ?? $entity->acquisitionCost,
                acquiredAt:                   $entity->acquiredAt,
                disposedAt:                   $entity->disposedAt,
                metadata:                     $dto->metadata ?? $entity->metadata,
                isActive:                     $dto->isActive ?? $entity->isActive,
                orgUnitId:                    $entity->orgUnitId,
                id:                           $entity->id,
            );

            return $this->repo->save($updated);
        });
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    public function find(int $id): ?Vehicle
    {
        return $this->repo->find($id);
    }

    public function listByTenant(int $tenantId, array $filters = []): array
    {
        return $this->repo->listByTenant($tenantId, $filters);
    }

    public function listAvailableForRental(int $tenantId): array
    {
        return $this->repo->listAvailableForRental($tenantId);
    }

    public function listAvailableForService(int $tenantId): array
    {
        return $this->repo->listAvailableForService($tenantId);
    }

    public function changeState(ChangeVehicleStateDTO $dto): Vehicle
    {
        return DB::transaction(function () use ($dto) {
            $vehicle = $this->repo->find($dto->vehicleId);

            if ($vehicle === null) {
                throw new \RuntimeException("Vehicle {$dto->vehicleId} not found.");
            }

            if (!VehicleState::canTransition($vehicle->currentState, $dto->toState)) {
                throw InvalidStateTransitionException::from(
                    $vehicle->registrationNumber,
                    $vehicle->currentState,
                    $dto->toState,
                );
            }

            $fromState = $vehicle->currentState;
            $now = now()->toDateTimeString();

            $this->repo->updateState($dto->vehicleId, $dto->toState, $now);

            $this->stateLogRepo->append(
                tenantId:      $vehicle->tenantId,
                vehicleId:     $dto->vehicleId,
                fromState:     $fromState,
                toState:       $dto->toState,
                reason:        $dto->reason,
                referenceType: $dto->referenceType,
                referenceId:   $dto->referenceId,
                triggeredBy:   $dto->triggeredBy,
            );

            Event::dispatch(new VehicleStateChanged(
                tenantId:           $vehicle->tenantId,
                vehicleId:          $dto->vehicleId,
                registrationNumber: $vehicle->registrationNumber,
                fromState:          $fromState,
                toState:            $dto->toState,
                referenceType:      $dto->referenceType,
                referenceId:        $dto->referenceId,
                triggeredBy:        $dto->triggeredBy,
            ));

            return $this->repo->find($dto->vehicleId);
        });
    }

    public function updateOdometer(int $vehicleId, string $odometer): void
    {
        $this->repo->updateOdometer($vehicleId, $odometer);
    }
}
