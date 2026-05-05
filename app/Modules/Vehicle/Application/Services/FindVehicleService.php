<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Vehicle\Application\Contracts\FindVehicleServiceInterface;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRepositoryInterface;

class FindVehicleService extends BaseService implements FindVehicleServiceInterface
{
    protected array $allowedSortColumns = ['id', 'make', 'model', 'rental_status', 'service_status', 'next_maintenance_due_at', 'created_at'];

    protected array $allowedFilterFields = ['tenant_id', 'ownership_type', 'rental_status', 'service_status', 'is_active', 'make', 'model'];

    public function __construct(VehicleRepositoryInterface $vehicleRepository)
    {
        parent::__construct($vehicleRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
