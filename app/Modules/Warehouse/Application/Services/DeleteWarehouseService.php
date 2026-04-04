<?php
namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Application\Contracts\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class DeleteWarehouseService implements DeleteWarehouseServiceInterface
{
    public function __construct(private readonly WarehouseRepositoryInterface $repository) {}

    public function execute(Warehouse $warehouse): bool
    {
        return $this->repository->delete($warehouse);
    }
}
