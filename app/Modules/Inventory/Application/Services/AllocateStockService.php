<?php
namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Contracts\AllocateStockServiceInterface;
use Modules\Inventory\Application\DTOs\AllocateStockData;
use Modules\Inventory\Domain\Events\StockReserved;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Domain\ValueObjects\AllocationAlgorithm;

class AllocateStockService implements AllocateStockServiceInterface
{
    private const FLOAT_TOLERANCE = 0.0001;
    public function __construct(
        private readonly InventoryLevelRepositoryInterface $repository
    ) {}

    /**
     * {@inheritdoc}
     */
    public function execute(AllocateStockData $data): array
    {
        AllocationAlgorithm::assertValid($data->allocationAlgorithm);

        $levels = $this->repository->findByProductForAllocation(
            $data->productId,
            $data->warehouseId,
            $data->allocationAlgorithm
        );

        $remaining   = $data->quantity;
        $allocations = [];

        foreach ($levels as $level) {
            if ($remaining <= 0) {
                break;
            }

            $available = $level->quantityAvailable;
            if ($available <= 0) {
                continue;
            }

            $take = min($remaining, $available);
            $level->reserve($take);
            $this->repository->save($level);

            $allocations[] = [
                'level_id' => $level->id,
                'quantity' => $take,
            ];

            Event::dispatch(new StockReserved($data->tenantId, $level->id));

            $remaining -= $take;
        }

        if ($remaining > self::FLOAT_TOLERANCE) {
            throw new \DomainException(
                "Insufficient available stock to allocate {$data->quantity} units "
                . "(shortfall: {$remaining})."
            );
        }

        return $allocations;
    }
}
