<?php
namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Contracts\ReconcileInventoryServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;
use Modules\Inventory\Domain\Events\CycleCountCompleted;
use Modules\Inventory\Domain\Events\InventoryReconciled;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;

class ReconcileInventoryService implements ReconcileInventoryServiceInterface
{
    public function __construct(private readonly InventoryCycleCountRepositoryInterface $repository) {}

    public function execute(int $cycleCountId, int $reconciledBy): InventoryCycleCount
    {
        $count = $this->repository->findById($cycleCountId);
        if (!$count) {
            throw new \DomainException("Cycle count [{$cycleCountId}] not found.");
        }

        $saved = $this->repository->update($count, [
            'status'       => 'completed',
            'completed_at' => now(),
            'updated_by'   => $reconciledBy,
        ]);

        Event::dispatch(new CycleCountCompleted($saved->tenantId, $saved->id));
        Event::dispatch(new InventoryReconciled($saved->tenantId, $saved->id));

        return $saved;
    }
}
