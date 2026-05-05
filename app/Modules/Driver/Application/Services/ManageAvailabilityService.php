<?php declare(strict_types=1);

namespace Modules\Driver\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Driver\Application\Contracts\ManageAvailabilityServiceInterface;
use Modules\Driver\Domain\Entities\Availability;
use Modules\Driver\Domain\RepositoryInterfaces\DriverAvailabilityRepositoryInterface;

class ManageAvailabilityService implements ManageAvailabilityServiceInterface
{
    public function __construct(
        private readonly DriverAvailabilityRepositoryInterface $availabilities,
    ) {}

    public function create(array $data): Availability
    {
        return DB::transaction(function () use ($data): Availability {
            $availability = new Availability(
                id: Str::uuid()->toString(),
                tenantId: (string) $data['tenant_id'],
                driverId: $data['driver_id'],
                availableFrom: new \DateTime($data['available_from']),
                availableTo: new \DateTime($data['available_to']),
                daysOfWeek: $data['days_of_week'] ?? null,
            );

            $this->availabilities->create($availability);
            return $availability;
        });
    }

    public function update(int $tenantId, string $id, array $data): Availability
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Availability {
            $availability = $this->availabilities->findById($id);
            if (!$availability || $availability->getTenantId() !== (string) $tenantId) {
                throw new \Exception('Availability record not found');
            }

            $this->availabilities->update($availability);
            return $this->availabilities->findById($id);
        });
    }

    public function find(int $tenantId, string $id): Availability
    {
        $availability = $this->availabilities->findById($id);
        if (!$availability || $availability->getTenantId() !== (string) $tenantId) {
            throw new \Exception('Availability record not found');
        }
        return $availability;
    }

    public function getByDriver(int $tenantId, string $driverId): array
    {
        return $this->availabilities->getByDriver((string) $tenantId, $driverId);
    }
}
