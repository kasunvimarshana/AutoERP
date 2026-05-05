<?php declare(strict_types=1);

namespace Modules\Asset\Application\Contracts;

use Modules\Asset\Domain\Entities\Vehicle;

interface ManageVehicleServiceInterface
{
    public function create(array $data): Vehicle;

    public function update(int $tenantId, string $id, array $data): Vehicle;

    public function find(int $tenantId, string $id): Vehicle;

    public function delete(int $tenantId, string $id): void;

    public function list(int $tenantId, int $perPage = 15, int $page = 1): array;

    public function getAvailableForRental(int $tenantId): array;

    public function updateStatus(int $tenantId, string $id, string $status): Vehicle;

    public function updateMileage(int $tenantId, string $id, int $mileage): Vehicle;
}
