<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\ReturnRefund\Application\Contracts\ReturnRefundServiceInterface;
use Modules\ReturnRefund\Application\DTOs\CreateReturnRefundDTO;
use Modules\ReturnRefund\Application\DTOs\UpdateReturnRefundDTO;
use Modules\ReturnRefund\Domain\Entities\ReturnRefund;
use Modules\ReturnRefund\Domain\Exceptions\ReturnRefundNotFoundException;
use Modules\ReturnRefund\Domain\RepositoryInterfaces\ReturnRefundRepositoryInterface;
use Modules\ReturnRefund\Domain\ValueObjects\ReturnStatus;

class ReturnRefundService implements ReturnRefundServiceInterface
{
    public function __construct(
        private readonly ReturnRefundRepositoryInterface $repository,
    ) {}

    public function getById(int $id, int $tenantId): ReturnRefund
    {
        $returnRefund = $this->repository->findById($id, $tenantId);

        if ($returnRefund === null) {
            throw new ReturnRefundNotFoundException($id);
        }

        return $returnRefund;
    }

    public function listByTenant(int $tenantId, array $filters = []): array
    {
        return $this->repository->findByTenant($tenantId, $filters);
    }

    public function listByRental(int $rentalId, int $tenantId): array
    {
        return $this->repository->findByRental($rentalId, $tenantId);
    }

    public function create(CreateReturnRefundDTO $dto): ReturnRefund
    {
        return DB::transaction(function () use ($dto): ReturnRefund {
            $entity = new ReturnRefund(
                id: null,
                tenantId: $dto->tenantId,
                orgUnitId: $dto->orgUnitId,
                rentalId: $dto->rentalId,
                returnNumber: $dto->returnNumber,
                status: ReturnStatus::Pending,
                returnedAt: $dto->returnedAt,
                endOdometer: $dto->endOdometer,
                actualDays: $dto->actualDays,
                rentalCharge: $dto->rentalCharge,
                extraCharges: $dto->extraCharges,
                damageCharges: $dto->damageCharges,
                fuelCharges: $dto->fuelCharges,
                depositPaid: $dto->depositPaid,
                refundAmount: $dto->refundAmount,
                refundMethod: $dto->refundMethod,
                inspectionNotes: $dto->inspectionNotes,
                notes: $dto->notes,
                damagePhotos: $dto->damagePhotos,
                metadata: $dto->metadata,
                isActive: true,
                rowVersion: 1,
            );

            return $this->repository->save($entity);
        });
    }

    public function update(int $id, int $tenantId, UpdateReturnRefundDTO $dto): ReturnRefund
    {
        return DB::transaction(function () use ($id, $tenantId, $dto): ReturnRefund {
            $existing = $this->getById($id, $tenantId);

            $updated = new ReturnRefund(
                id: $existing->id,
                tenantId: $existing->tenantId,
                orgUnitId: $existing->orgUnitId,
                rentalId: $existing->rentalId,
                returnNumber: $existing->returnNumber,
                status: $existing->status,
                returnedAt: $existing->returnedAt,
                endOdometer: $dto->endOdometer ?? $existing->endOdometer,
                actualDays: $dto->actualDays ?? $existing->actualDays,
                rentalCharge: $dto->rentalCharge ?? $existing->rentalCharge,
                extraCharges: $dto->extraCharges ?? $existing->extraCharges,
                damageCharges: $dto->damageCharges ?? $existing->damageCharges,
                fuelCharges: $dto->fuelCharges ?? $existing->fuelCharges,
                depositPaid: $dto->depositPaid ?? $existing->depositPaid,
                refundAmount: $dto->refundAmount ?? $existing->refundAmount,
                refundMethod: $dto->refundMethod ?? $existing->refundMethod,
                inspectionNotes: $dto->inspectionNotes ?? $existing->inspectionNotes,
                notes: $dto->notes ?? $existing->notes,
                damagePhotos: $dto->damagePhotos ?? $existing->damagePhotos,
                metadata: $dto->metadata ?? $existing->metadata,
                isActive: $existing->isActive,
                rowVersion: $existing->rowVersion,
            );

            return $this->repository->save($updated);
        });
    }

    public function changeStatus(int $id, int $tenantId, ReturnStatus $status): ReturnRefund
    {
        return DB::transaction(function () use ($id, $tenantId, $status): ReturnRefund {
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
