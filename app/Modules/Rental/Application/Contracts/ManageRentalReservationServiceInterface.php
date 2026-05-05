<?php declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalReservation;

interface ManageRentalReservationServiceInterface
{
    public function create(array $data): RentalReservation;

    public function update(int $tenantId, string $id, array $data): RentalReservation;

    public function find(int $tenantId, string $id): RentalReservation;

    public function list(int $tenantId, int $perPage = 15, int $page = 1): array;

    public function confirm(int $tenantId, string $id): RentalReservation;

    public function cancel(int $tenantId, string $id): RentalReservation;
}
