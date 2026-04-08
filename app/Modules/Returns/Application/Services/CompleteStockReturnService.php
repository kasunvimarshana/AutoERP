<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\CompleteStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\ProcessReturnInventoryAdjustmentServiceInterface;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Events\StockReturnCompleted;
use Modules\Returns\Domain\Exceptions\StockReturnNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;

class CompleteStockReturnService extends BaseService implements CompleteStockReturnServiceInterface
{
    public function __construct(
        private readonly StockReturnRepositoryInterface $returnRepository,
        private readonly ProcessReturnInventoryAdjustmentServiceInterface $inventoryAdjustmentService,
    ) {
        parent::__construct($returnRepository);
    }

    protected function handle(array $data): StockReturn
    {
        $id     = (int) $data['id'];
        $return = $this->returnRepository->find($id);

        if (! $return) {
            throw new StockReturnNotFoundException($id);
        }

        $return->complete((int) $data['processed_by']);

        $saved = $this->returnRepository->save($return);
        $this->addEvent(new StockReturnCompleted($saved));

        // Orchestrate inventory layer adjustments for all approved return lines.
        // Creates StockMovement records, adjusts InventoryLevel quantities,
        // and adds new InventoryValuationLayer entries per the tenant's valuation method.
        $this->inventoryAdjustmentService->execute(['id' => $id]);

        return $saved;
    }
}
