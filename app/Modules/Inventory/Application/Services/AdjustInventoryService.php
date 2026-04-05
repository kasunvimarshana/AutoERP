<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\AdjustInventoryServiceInterface;
use Modules\Inventory\Application\DTOs\AdjustInventoryData;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Events\InventoryAdjusted;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class AdjustInventoryService implements AdjustInventoryServiceInterface
{
    public function __construct(private readonly InventoryLevelRepositoryInterface $levelRepo) {}

    public function execute(AdjustInventoryData $data): InventoryLevel
    {
        $level = $this->levelRepo->upsert(
            $data->tenant_id, $data->product_id, $data->warehouse_id, $data->location_id, 'fifo'
        );

        $diff = $level->adjust($data->new_quantity);
        $this->levelRepo->update($level->getId(), [
            'quantity_on_hand' => $level->getQuantityOnHand(),
        ]);

        event(new InventoryAdjusted($data->tenant_id, $data->product_id, $data->warehouse_id, $diff));

        return $level;
    }
}
