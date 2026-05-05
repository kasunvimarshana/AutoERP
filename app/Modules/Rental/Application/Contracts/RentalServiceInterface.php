<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Application\DTOs\CreateRentalDTO;
use Modules\Rental\Application\DTOs\UpdateRentalDTO;
use Modules\Rental\Domain\Entities\Rental;

interface RentalServiceInterface
{
    public function getById(int $id, int $tenantId): Rental;

    /** @return Rental[] */
    public function listByTenant(int $tenantId, array $filters = []): array;

    /** @return Rental[] */
    public function listByCustomer(int $customerId, int $tenantId): array;

    public function create(CreateRentalDTO $dto): Rental;

    public function update(int $id, int $tenantId, UpdateRentalDTO $dto): Rental;

    public function confirm(int $id, int $tenantId): Rental;

    public function start(int $id, int $tenantId, string $actualStartAt, string|null $startOdometer): Rental;

    public function complete(int $id, int $tenantId, string $actualEndAt, string|null $endOdometer): Rental;

    public function cancel(int $id, int $tenantId, string $reason): Rental;

    public function delete(int $id, int $tenantId): void;
}
