<?php
namespace Modules\Warehouse\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseData;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Events\WarehouseCreated;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class CreateWarehouseService implements CreateWarehouseServiceInterface
{
    public function __construct(private readonly WarehouseRepositoryInterface $repository) {}

    public function execute(WarehouseData $data): Warehouse
    {
        $warehouse = $this->repository->create($data->toArray());
        Event::dispatch(new WarehouseCreated($warehouse->tenantId, $warehouse->id));
        return $warehouse;
    }
}
