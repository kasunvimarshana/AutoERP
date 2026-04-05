<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\IssueStockServiceInterface;
use Modules\Inventory\Application\DTOs\IssueStockData;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Events\StockIssued;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;

class IssueStockService implements IssueStockServiceInterface
{
    public function __construct(
        private readonly InventoryLevelRepositoryInterface $levelRepo,
        private readonly InventoryValuationLayerRepositoryInterface $layerRepo,
    ) {}

    public function execute(IssueStockData $data): InventoryLevel
    {
        return DB::transaction(function () use ($data): InventoryLevel {
            $level = $this->levelRepo->findByProduct($data->tenant_id, $data->product_id, $data->warehouse_id);
            if (!$level) {
                throw new \DomainException("No inventory level found for product [{$data->product_id}] in warehouse [{$data->warehouse_id}].");
            }

            $level->issue($data->quantity);
            $this->levelRepo->update($level->getId(), [
                'quantity_on_hand' => $level->getQuantityOnHand(),
            ]);

            // Consume valuation layers (FIFO/LIFO/Average)
            $remaining = $data->quantity;
            $layers    = $this->layerRepo->findLayersForConsumption(
                $data->tenant_id, $data->product_id, $data->warehouse_id, $data->allocation_strategy
            );
            foreach ($layers as $layer) {
                if ($remaining <= InventoryLevel::FLOAT_TOLERANCE) break;
                $consume = min($remaining, $layer->getQuantityRemaining());
                $layer->consume($consume);
                $this->layerRepo->update($layer->getId(), [
                    'quantity_remaining' => $layer->getQuantityRemaining(),
                ]);
                $remaining -= $consume;
            }

            event(new StockIssued($data->tenant_id, $data->product_id, $data->warehouse_id, $data->quantity));

            return $level;
        });
    }
}
