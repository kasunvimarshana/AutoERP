<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Rental\Application\Contracts\RentalChargeServiceInterface;
use Modules\Rental\Application\DTOs\CreateRentalChargeDTO;
use Modules\Rental\Domain\Entities\RentalCharge;
use Modules\Rental\Domain\Exceptions\RentalNotFoundException;
use Modules\Rental\Domain\RepositoryInterfaces\RentalChargeRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalRepositoryInterface;

class RentalChargeService implements RentalChargeServiceInterface
{
    public function __construct(
        private readonly RentalChargeRepositoryInterface $chargeRepository,
        private readonly RentalRepositoryInterface $rentalRepository,
    ) {}

    public function getById(int $id, int $tenantId): RentalCharge
    {
        $charge = $this->chargeRepository->findById($id, $tenantId);
        if ($charge === null) {
            throw new RentalNotFoundException($id);
        }
        return $charge;
    }

    public function listByRental(int $rentalId, int $tenantId): array
    {
        return $this->chargeRepository->findByRental($rentalId, $tenantId);
    }

    public function create(CreateRentalChargeDTO $dto): RentalCharge
    {
        return DB::transaction(function () use ($dto): RentalCharge {
            $rental = $this->rentalRepository->findById($dto->rentalId, $dto->tenantId);
            if ($rental === null) {
                throw new RentalNotFoundException($dto->rentalId);
            }
            $amount = bcmul($dto->quantity, $dto->unitPrice, 6);
            $charge = new RentalCharge(
                id: null,
                tenantId: $dto->tenantId,
                rentalId: $dto->rentalId,
                chargeType: $dto->chargeType,
                description: $dto->description,
                quantity: $dto->quantity,
                unitPrice: $dto->unitPrice,
                amount: $amount,
                isActive: true,
            );
            return $this->chargeRepository->save($charge);
        });
    }

    public function delete(int $id, int $tenantId): void
    {
        DB::transaction(function () use ($id, $tenantId): void {
            $this->getById($id, $tenantId);
            $this->chargeRepository->delete($id, $tenantId);
        });
    }
}
