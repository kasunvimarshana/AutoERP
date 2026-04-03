<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\AdjustInventoryServiceInterface;
use Modules\Inventory\Application\DTOs\AdjustInventoryData;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Events\InventoryAdjusted;
use Modules\Inventory\Domain\Exceptions\InventoryLevelNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class AdjustInventoryService extends BaseService implements AdjustInventoryServiceInterface
{
    public function __construct(private readonly InventoryLevelRepositoryInterface $levelRepository)
    {
        parent::__construct($levelRepository);
    }

    protected function handle(array $data): InventoryLevel
    {
        $dto   = AdjustInventoryData::fromArray($data);
        $level = $this->levelRepository->find($dto->id);

        if (! $level) {
            throw new InventoryLevelNotFoundException($dto->id);
        }

        if ($dto->adjustmentQty >= 0.0) {
            $level->addStock($dto->adjustmentQty);
        } else {
            $level->removeStock(abs($dto->adjustmentQty), allowNegative: false);
        }

        $saved = $this->levelRepository->save($level);
        $this->addEvent(new InventoryAdjusted($saved, $dto->adjustmentQty, $dto->reason));

        return $saved;
    }
}
