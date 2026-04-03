<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\ReconcileInventoryServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;
use Modules\Inventory\Domain\Events\InventoryReconciled;
use Modules\Inventory\Domain\Exceptions\InventoryCycleCountNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;

class ReconcileInventoryService extends BaseService implements ReconcileInventoryServiceInterface
{
    public function __construct(private readonly InventoryCycleCountRepositoryInterface $cycleCountRepository)
    {
        parent::__construct($cycleCountRepository);
    }

    protected function handle(array $data): InventoryCycleCount
    {
        $id         = (int) $data['id'];
        $cycleCount = $this->cycleCountRepository->find($id);

        if (! $cycleCount) {
            throw new InventoryCycleCountNotFoundException($id);
        }

        $cycleCount->complete();

        $saved = $this->cycleCountRepository->save($cycleCount);
        $this->addEvent(new InventoryReconciled($saved));

        return $saved;
    }
}
