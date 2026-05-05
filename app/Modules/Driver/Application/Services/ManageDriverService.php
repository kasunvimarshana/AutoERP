<?php declare(strict_types=1);

namespace Modules\Driver\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Driver\Application\Contracts\ManageDriverServiceInterface;
use Modules\Driver\Domain\Entities\Driver;
use Modules\Driver\Domain\RepositoryInterfaces\DriverRepositoryInterface;

class ManageDriverService implements ManageDriverServiceInterface
{
    public function __construct(
        private readonly DriverRepositoryInterface $drivers,
    ) {}

    public function create(array $data): Driver
    {
        return DB::transaction(function () use ($data): Driver {
            $driver = new Driver(
                id: Str::uuid()->toString(),
                tenantId: (string) $data['tenant_id'],
                employeeId: $data['employee_id'] ?? null,
                firstName: $data['first_name'],
                lastName: $data['last_name'],
                email: $data['email'],
                phone: $data['phone'],
                dateOfBirth: new \DateTime($data['date_of_birth']),
                driverType: $data['driver_type'] ?? 'contractor',
                address: $data['address'] ?? null,
                idNumber: $data['id_number'] ?? null,
                hireDate: new \DateTime($data['hire_date'] ?? 'now'),
                isAvailable: $data['is_available'] ?? true,
            );

            $this->drivers->create($driver);
            return $driver;
        });
    }

    public function update(int $tenantId, string $id, array $data): Driver
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Driver {
            $driver = $this->drivers->findById($id);
            if (!$driver || $driver->getTenantId() !== (string) $tenantId) {
                throw new \Exception('Driver not found');
            }

            $this->drivers->update($driver);
            return $this->drivers->findById($id);
        });
    }

    public function find(int $tenantId, string $id): Driver
    {
        $driver = $this->drivers->findById($id);
        if (!$driver || $driver->getTenantId() !== (string) $tenantId) {
            throw new \Exception('Driver not found');
        }
        return $driver;
    }

    public function delete(int $tenantId, string $id): void
    {
        $this->find($tenantId, $id);
        $this->drivers->delete($id);
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1): array
    {
        return $this->drivers->getAllByTenant((string) $tenantId, $page, $perPage);
    }

    public function getAvailable(int $tenantId): array
    {
        return $this->drivers->getActive((string) $tenantId);
    }
}
