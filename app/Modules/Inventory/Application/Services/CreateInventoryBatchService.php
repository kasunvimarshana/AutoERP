<?php
namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Contracts\CreateInventoryBatchServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryBatch;
use Modules\Inventory\Domain\Events\InventoryBatchCreated;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;

class CreateInventoryBatchService implements CreateInventoryBatchServiceInterface
{
    public function __construct(private readonly InventoryBatchRepositoryInterface $repository) {}

    public function execute(array $data): InventoryBatch
    {
        $batch = $this->repository->create($data);

        Event::dispatch(new InventoryBatchCreated($batch->tenantId, $batch->id));

        return $batch;
    }
}
