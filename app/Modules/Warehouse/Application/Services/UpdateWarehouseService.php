<?php
namespace Modules\Warehouse\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseData;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Events\WarehouseUpdated;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class UpdateWarehouseService implements UpdateWarehouseServiceInterface
{
    public function __construct(private readonly WarehouseRepositoryInterface $repository) {}

    public function execute(Warehouse $warehouse, WarehouseData $data): Warehouse
    {
        $updated = $this->repository->update($warehouse, $data->toArray());
        Event::dispatch(new WarehouseUpdated($warehouse->tenantId, $warehouse->id));
        return $updated;
    }
}
