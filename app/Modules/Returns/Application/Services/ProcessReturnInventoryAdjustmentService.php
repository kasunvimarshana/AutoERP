<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\ProcessReturnInventoryAdjustmentServiceInterface;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Events\StockReturnInventoryAdjusted;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;

class ProcessReturnInventoryAdjustmentService implements ProcessReturnInventoryAdjustmentServiceInterface
{
    public function __construct(
        private readonly StockReturnLineRepositoryInterface $lineRepository,
    ) {}

    public function execute(StockReturn $return): void
    {
        // Lines are available for downstream consumers via the event.
        // Cross-module inventory adjustment is handled by event listeners.
        Event::dispatch(new StockReturnInventoryAdjusted($return->tenantId, $return->id));
    }
}
