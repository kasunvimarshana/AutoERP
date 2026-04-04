<?php
namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Contracts\CreateCycleCountServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;
use Modules\Inventory\Domain\Events\CycleCountCreated;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;

class CreateCycleCountService implements CreateCycleCountServiceInterface
{
    public function __construct(private readonly InventoryCycleCountRepositoryInterface $repository) {}

    public function execute(array $data): InventoryCycleCount
    {
        $count = $this->repository->create($data);

        Event::dispatch(new CycleCountCreated($count->tenantId, $count->id));

        return $count;
    }
}
