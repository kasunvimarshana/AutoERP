<?php
namespace Modules\Warehouse\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Warehouse\Application\Contracts\CreateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseZoneData;
use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Domain\Events\WarehouseZoneCreated;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;

class CreateWarehouseZoneService implements CreateWarehouseZoneServiceInterface
{
    public function __construct(
        private readonly WarehouseZoneRepositoryInterface $zoneRepository,
        private readonly WarehouseRepositoryInterface $warehouseRepository,
    ) {}

    public function execute(WarehouseZoneData $data): WarehouseZone
    {
        $warehouse = $this->warehouseRepository->findById($data->warehouseId);
        if ($warehouse === null) {
            throw new \InvalidArgumentException("Warehouse with ID {$data->warehouseId} not found.");
        }
        $zone = $this->zoneRepository->create($data->toArray());
        Event::dispatch(new WarehouseZoneCreated($warehouse->tenantId, $zone->id));
        return $zone;
    }
}
