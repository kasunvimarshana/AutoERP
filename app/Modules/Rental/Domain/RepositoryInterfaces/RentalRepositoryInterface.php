<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\Rental;
use Modules\Rental\Domain\ValueObjects\RentalStatus;

interface RentalRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Rental;

    /** @return Rental[] */
    public function findByTenant(int $tenantId, array $filters = []): array;

    /** @return Rental[] */
    public function findActiveByVehicle(int $vehicleId, int $tenantId): array;

    /** @return Rental[] */
    public function findByCustomer(int $customerId, int $tenantId): array;

    public function save(Rental $rental): Rental;

    public function updateStatus(int $id, int $tenantId, RentalStatus $status, array $extraData = []): void;

    public function delete(int $id, int $tenantId): void;
}
