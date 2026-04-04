<?php
namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Application\Contracts\UpdateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseLocationData;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;

class UpdateWarehouseLocationService implements UpdateWarehouseLocationServiceInterface
{
    public function __construct(private readonly WarehouseLocationRepositoryInterface $repository) {}

    public function execute(WarehouseLocation $location, WarehouseLocationData $data): WarehouseLocation
    {
        return $this->repository->update($location, $data->toArray());
    }
}
