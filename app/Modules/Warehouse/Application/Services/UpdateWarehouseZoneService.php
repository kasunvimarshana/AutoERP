<?php
namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Application\Contracts\UpdateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseZoneData;
use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;

class UpdateWarehouseZoneService implements UpdateWarehouseZoneServiceInterface
{
    public function __construct(private readonly WarehouseZoneRepositoryInterface $repository) {}

    public function execute(WarehouseZone $zone, WarehouseZoneData $data): WarehouseZone
    {
        return $this->repository->update($zone, $data->toArray());
    }
}
