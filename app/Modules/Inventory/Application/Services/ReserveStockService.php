<?php
namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Contracts\ReserveStockServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Events\StockReserved;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class ReserveStockService implements ReserveStockServiceInterface
{
    public function __construct(private readonly InventoryLevelRepositoryInterface $repository) {}

    public function execute(int $levelId, float $qty): InventoryLevel
    {
        $level = $this->repository->findById($levelId);
        if (!$level) {
            throw new \DomainException("Inventory level [{$levelId}] not found.");
        }

        $level->reserve($qty);
        $saved = $this->repository->save($level);

        Event::dispatch(new StockReserved($saved->tenantId, $saved->id));

        return $saved;
    }
}
