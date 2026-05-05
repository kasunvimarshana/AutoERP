<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Application\DTOs\CreateRentalChargeDTO;
use Modules\Rental\Domain\Entities\RentalCharge;

interface RentalChargeServiceInterface
{
    public function getById(int $id, int $tenantId): RentalCharge;

    /** @return RentalCharge[] */
    public function listByRental(int $rentalId, int $tenantId): array;

    public function create(CreateRentalChargeDTO $dto): RentalCharge;

    public function delete(int $id, int $tenantId): void;
}
