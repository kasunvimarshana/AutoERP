<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\CompleteStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\ProcessReturnInventoryAdjustmentServiceInterface;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Events\StockReturnCompleted;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\ReturnStatus;

class CompleteStockReturnService implements CompleteStockReturnServiceInterface
{
    public function __construct(
        private readonly StockReturnRepositoryInterface $repository,
        private readonly ProcessReturnInventoryAdjustmentServiceInterface $inventoryAdjustmentService,
    ) {}

    public function execute(StockReturn $return, int $completedBy): StockReturn
    {
        $updated = $this->repository->update($return, [
            'status'       => ReturnStatus::COMPLETED,
            'completed_by' => $completedBy,
            'completed_at' => now(),
        ]);

        $this->inventoryAdjustmentService->execute($updated);

        Event::dispatch(new StockReturnCompleted($updated->tenantId, $updated->id));

        return $updated;
    }
}
