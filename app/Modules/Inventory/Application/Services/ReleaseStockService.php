<?php
namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Application\Contracts\ReleaseStockServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Events\StockReleased;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class ReleaseStockService implements ReleaseStockServiceInterface
{
    public function __construct(private readonly InventoryLevelRepositoryInterface $repository) {}

    public function execute(int $levelId, float $qty): InventoryLevel
    {
        $level = $this->repository->findById($levelId);
        if (!$level) {
            throw new \DomainException("Inventory level [{$levelId}] not found.");
        }

        $level->release($qty);
        $saved = $this->repository->save($level);

        Event::dispatch(new StockReleased($saved->tenantId, $saved->id));

        return $saved;
    }
}
