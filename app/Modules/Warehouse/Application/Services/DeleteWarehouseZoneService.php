<?php
namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Application\Contracts\DeleteWarehouseZoneServiceInterface;
use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;

class DeleteWarehouseZoneService implements DeleteWarehouseZoneServiceInterface
{
    public function __construct(private readonly WarehouseZoneRepositoryInterface $repository) {}

    public function execute(WarehouseZone $zone): bool
    {
        return $this->repository->delete($zone);
    }
}
