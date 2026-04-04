<?php
namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Contracts\AllocateStockServiceInterface;
use Modules\Inventory\Application\Contracts\ConsumeValuationLayersServiceInterface;
use Modules\Inventory\Application\Contracts\IssueStockServiceInterface;
use Modules\Inventory\Application\DTOs\AllocateStockData;
use Modules\Inventory\Application\DTOs\ConsumeValuationLayersData;
use Modules\Inventory\Application\DTOs\IssueStockData;
use Modules\Inventory\Domain\Events\InventoryLevelUpdated;
use Modules\Inventory\Domain\Events\StockIssued;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

/**
 * Outbound stock issue orchestrator.
 *
 * 1. Allocates (reserves) stock from InventoryLevel records using the configured algorithm.
 * 2. Consumes matching InventoryValuationLayers (FIFO/LIFO/Average).
 * 3. Confirms physical issuance on each InventoryLevel (reduces on-hand).
 * 4. Dispatches StockIssued + InventoryLevelUpdated events per level.
 */
class IssueStockService implements IssueStockServiceInterface
{
    public function __construct(
        private readonly AllocateStockServiceInterface $allocateService,
        private readonly ConsumeValuationLayersServiceInterface $consumeService,
        private readonly InventoryLevelRepositoryInterface $levelRepository,
    ) {}

    public function execute(IssueStockData $data): array
    {
        // 1. Allocate stock (reserves qty across levels using the configured algorithm)
        $allocations = $this->allocateService->execute(new AllocateStockData(
            tenantId:            $data->tenantId,
            productId:           $data->productId,
            warehouseId:         $data->warehouseId,
            quantity:            $data->quantity,
            allocationAlgorithm: $data->allocationAlgorithm,
        ));

        // 2. Consume valuation layers to determine COGS
        $totalCost = $this->consumeService->execute(new ConsumeValuationLayersData(
            tenantId:        $data->tenantId,
            productId:       $data->productId,
            warehouseId:     $data->warehouseId,
            valuationMethod: $data->valuationMethod,
            quantity:        $data->quantity,
            referenceType:   $data->referenceType,
            referenceId:     $data->referenceId,
        ));

        // 3. Confirm physical issuance: reduce on-hand on each allocated level
        foreach ($allocations as $allocation) {
            $level = $this->levelRepository->findById($allocation['level_id']);
            if ($level === null) {
                continue;
            }

            $level->issue($allocation['quantity']);
            $this->levelRepository->save($level);

            Event::dispatch(new StockIssued($data->tenantId, $level->id));
            Event::dispatch(new InventoryLevelUpdated($data->tenantId, $level->id));
        }

        return [
            'allocations' => $allocations,
            'total_cost'  => $totalCost,
        ];
    }
}
