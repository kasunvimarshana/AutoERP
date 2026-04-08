<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\ReserveStockServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Events\StockReserved;
use Modules\Inventory\Domain\Exceptions\InventoryLevelNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class ReserveStockService extends BaseService implements ReserveStockServiceInterface
{
    public function __construct(private readonly InventoryLevelRepositoryInterface $levelRepository)
    {
        parent::__construct($levelRepository);
    }

    protected function handle(array $data): InventoryLevel
    {
        $id  = (int) $data['id'];
        $qty = (float) $data['qty'];

        $level = $this->levelRepository->find($id);

        if (! $level) {
            throw new InventoryLevelNotFoundException($id);
        }

        $level->reserve($qty);

        $saved = $this->levelRepository->save($level);
        $this->addEvent(new StockReserved($saved, $qty));

        return $saved;
    }
}
