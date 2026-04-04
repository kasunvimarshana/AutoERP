<?php
namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Application\Contracts\DeleteWarehouseLocationServiceInterface;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;

class DeleteWarehouseLocationService implements DeleteWarehouseLocationServiceInterface
{
    public function __construct(private readonly WarehouseLocationRepositoryInterface $repository) {}

    public function execute(WarehouseLocation $location): bool
    {
        return $this->repository->delete($location);
    }
}
