<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\ServiceCenter\Application\Contracts\ServiceJobServiceInterface;
use Modules\ServiceCenter\Application\DTOs\CreateServiceJobDTO;
use Modules\ServiceCenter\Application\DTOs\UpdateServiceJobDTO;
use Modules\ServiceCenter\Domain\Entities\ServiceJob;
use Modules\ServiceCenter\Domain\Exceptions\ServiceJobNotFoundException;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceJobRepositoryInterface;
use Modules\ServiceCenter\Domain\ValueObjects\ServiceJobStatus;

class ServiceJobService implements ServiceJobServiceInterface
{
    public function __construct(
        private readonly ServiceJobRepositoryInterface $repository,
    ) {}

    public function getById(int $id, int $tenantId): ServiceJob
    {
        $serviceJob = $this->repository->findById($id, $tenantId);

        if ($serviceJob === null) {
            throw new ServiceJobNotFoundException($id);
        }

        return $serviceJob;
    }

    public function listByTenant(int $tenantId, array $filters = []): array
    {
        return $this->repository->findByTenant($tenantId, $filters);
    }

    public function listByVehicle(int $vehicleId, int $tenantId): array
    {
        return $this->repository->findByVehicle($vehicleId, $tenantId);
    }

    public function create(CreateServiceJobDTO $dto): ServiceJob
    {
        return DB::transaction(function () use ($dto): ServiceJob {
            $entity = new ServiceJob(
                id: null,
                tenantId: $dto->tenantId,
                orgUnitId: $dto->orgUnitId,
                vehicleId: $dto->vehicleId,
                driverId: $dto->driverId,
                jobNumber: $dto->jobNumber,
                jobType: $dto->jobType,
                status: ServiceJobStatus::Pending,
                scheduledAt: $dto->scheduledAt,
                startedAt: $dto->startedAt,
                completedAt: $dto->completedAt,
                odometerIn: $dto->odometerIn,
                odometerOut: $dto->odometerOut,
                description: $dto->description,
                partsCost: $dto->partsCost,
                labourCost: $dto->labourCost,
                totalCost: $dto->totalCost,
                technicianNotes: $dto->technicianNotes,
                customerApproval: $dto->customerApproval,
                metadata: $dto->metadata,
                isActive: true,
                rowVersion: 1,
            );

            return $this->repository->save($entity);
        });
    }

    public function update(int $id, int $tenantId, UpdateServiceJobDTO $dto): ServiceJob
    {
        return DB::transaction(function () use ($id, $tenantId, $dto): ServiceJob {
            $existing = $this->getById($id, $tenantId);

            $updated = new ServiceJob(
                id: $existing->id,
                tenantId: $existing->tenantId,
                orgUnitId: $existing->orgUnitId,
                vehicleId: $existing->vehicleId,
                driverId: $existing->driverId,
                jobNumber: $existing->jobNumber,
                jobType: $dto->jobType ?? $existing->jobType,
                status: $existing->status,
                scheduledAt: $dto->scheduledAt ?? $existing->scheduledAt,
                startedAt: $dto->startedAt ?? $existing->startedAt,
                completedAt: $dto->completedAt ?? $existing->completedAt,
                odometerIn: $dto->odometerIn ?? $existing->odometerIn,
                odometerOut: $dto->odometerOut ?? $existing->odometerOut,
                description: $dto->description ?? $existing->description,
                partsCost: $dto->partsCost ?? $existing->partsCost,
                labourCost: $dto->labourCost ?? $existing->labourCost,
                totalCost: $dto->totalCost ?? $existing->totalCost,
                technicianNotes: $dto->technicianNotes ?? $existing->technicianNotes,
                customerApproval: $dto->customerApproval ?? $existing->customerApproval,
                metadata: $dto->metadata ?? $existing->metadata,
                isActive: $existing->isActive,
                rowVersion: $existing->rowVersion,
            );

            return $this->repository->save($updated);
        });
    }

    public function changeStatus(int $id, int $tenantId, ServiceJobStatus $status): ServiceJob
    {
        return DB::transaction(function () use ($id, $tenantId, $status): ServiceJob {
            $this->getById($id, $tenantId);
            $this->repository->updateStatus($id, $tenantId, $status);

            return $this->getById($id, $tenantId);
        });
    }

    public function delete(int $id, int $tenantId): void
    {
        DB::transaction(function () use ($id, $tenantId): void {
            $this->getById($id, $tenantId);
            $this->repository->delete($id, $tenantId);
        });
    }
}
