<?php
namespace Modules\Warehouse\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseLocationData;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Domain\Events\WarehouseLocationCreated;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class CreateWarehouseLocationService implements CreateWarehouseLocationServiceInterface
{
    public function __construct(
        private readonly WarehouseLocationRepositoryInterface $locationRepository,
        private readonly WarehouseRepositoryInterface $warehouseRepository,
    ) {}

    public function execute(WarehouseLocationData $data): WarehouseLocation
    {
        $warehouse = $this->warehouseRepository->findById($data->warehouseId);
        if ($warehouse === null) {
            throw new \InvalidArgumentException("Warehouse with ID {$data->warehouseId} not found.");
        }
        $location = $this->locationRepository->create($data->toArray());
        Event::dispatch(new WarehouseLocationCreated($warehouse->tenantId, $location->id));
        return $location;
    }
}
